<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Genre;
use App\Models\Studio;
use App\Models\Tag;
use App\Models\Rating;
use App\Models\Favorite;
use App\Models\Comment;
use App\Models\WatchHistory;
use App\Models\EpisodeSource;
use App\Models\Subtitle;
use App\Services\AnilistVideoSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Helper function for auth check */
function user_check(): bool {
    return Auth::check();
}

/** Helper function to get user id */
function user_id(): ?int {
    return Auth::id();
}

class AnimeController extends Controller
{
    // ─── Main Pages ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Anime::query()->with(['genres', 'studio']);

        // Filters
        if ($request->has('genre') && $request->genre) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('slug', $request->genre);
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('year') && $request->year) {
            $query->where('aired_year', $request->year);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('synopsis', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $allowedSorts = ['title', 'aired_year', 'rating', 'views_count', 'created_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $anime = $query->paginate(24)->withQueryString();

        return view('anime.index', compact('anime'));
    }

    public function show(string $slug)
    {
        $anime = Anime::where('slug', $slug)
            ->with(['genres', 'studio', 'episodes' => function ($q) {
                $q->orderBy('number');
            }])
            ->firstOrFail();

        // Related anime
        $related = Anime::whereHas('genres', function ($q) use ($anime) {
            $q->whereIn('genres.id', $anime->genres->pluck('id'));
        })
        ->where('id', '!=', $anime->id)
        ->limit(6)
        ->get();

        // User interactions
        $userRating = null;
        $isFavorite = false;
        $userWatchProgress = null;

        if (auth()->check()) {
            $userId = auth()->id();
            $userRating = Rating::where('anime_id', $anime->id)
                ->where('user_id', $userId)
                ->first();
            $isFavorite = Favorite::where('anime_id', $anime->id)
                ->where('user_id', $userId)
                ->exists();
        }

        // Increment views
        $anime->increment('views_count');

        return view('anime.show', compact('anime', 'related', 'userRating', 'isFavorite', 'userWatchProgress'));
    }

public function stream(string $slug, int $episodeNumber)
    {
        $anime = Anime::where('slug', $slug)->firstOrFail();
        
        $episode = \App\Models\Episode::where('anime_id', $anime->id)
            ->where('number', $episodeNumber)
            ->with(['sources' => function ($q) {
                $q->orderByDesc('quality');
            }, 'subtitles'])
            ->firstOrFail();

        // Auto-fetch sources if none exist
        if ($episode->sources->isEmpty()) {
            $fetched = $this->fetchSourcesOnTheFly($anime, $episode);
            $episode->load(['sources' => function ($q) {
                $q->orderByDesc('quality');
            }, 'subtitles']);
            if ($episode->sources->isEmpty()) {
                session()->flash('warning', $fetched ?: 'Could not find video sources for this episode.');
            }
        }

        // Next/Previous episodes
        $nextEpisode = $anime->episodes()->where('number', '>', $episodeNumber)->orderBy('number')->first();
        $prevEpisode = $anime->episodes()->where('number', '<', $episodeNumber)->orderByDesc('number')->first();

        // Update watch history
        if (auth()->check()) {
            $userId = auth()->id();
            \App\Models\WatchHistory::updateOrCreate(
                ['user_id' => $userId, 'episode_id' => $episode->id],
                ['anime_id' => $anime->id, 'watched_at' => now()]
            );
        }

        return view('anime.stream', compact('episode', 'anime', 'nextEpisode', 'prevEpisode'));
    }

    private function fetchSourcesOnTheFly($anime, $episode): ?string
    {
        try {
            $service = app(AnilistVideoSourceService::class);
            $titles = array_unique(array_filter([
                $anime->title,
                $anime->title_english,
                $anime->title_japanese,
            ]));

            $result = null;
            foreach ($titles as $t) {
                $result = $service->fetchEpisodeSources(
                    (int) ($anime->anilist_id ?? 0), (int) $episode->number, $t
                );
                if (empty($result['error']) && !empty($result['sources'])) {
                    break;
                }
            }

            if (!$result || !empty($result['error']) || empty($result['sources'])) {
                $err = $result['error'] ?? 'All providers returned no sources';
                logger()->warning('On-the-fly source fetch failed', [
                    'anime' => $anime->id, 'episode' => $episode->id, 'titles' => $titles,
                    'error' => $err,
                ]);
                return $err;
            }

            DB::transaction(function () use ($episode, $result) {
                $sort = 0;
                foreach ($result['sources'] as $s) {
                    $url = (string) ($s['url'] ?? '');
                    if ($url === '') continue;

                    EpisodeSource::create([
                        'episode_id' => $episode->id,
                        'video_server_id' => null,
                        'label' => (string) ($s['label'] ?? $s['quality'] ?? '720p'),
                        'quality' => in_array((string) ($s['quality'] ?? '720p'), ['360p', '480p', '720p', '1080p', '4K'], true) ? $s['quality'] : '720p',
                        'url' => $url,
                        'headers' => $s['headers'] ?? null,
                        'type' => (string) ($s['type'] ?? 'hls'),
                        'language' => (string) ($s['language'] ?? 'sub'),
                        'is_active' => true,
                        'sort_order' => $sort++,
                    ]);
                }

                foreach ($result['subtitles'] ?? [] as $t) {
                    $filePath = (string) ($t['file_path'] ?? '');
                    if ($filePath === '') continue;

                    Subtitle::create([
                        'episode_id' => $episode->id,
                        'language' => (string) ($t['language'] ?? 'en'),
                        'label' => (string) ($t['label'] ?? strtoupper((string) ($t['language'] ?? 'en'))),
                        'file_path' => $filePath,
                        'is_default' => (bool) ($t['is_default'] ?? false),
                    ]);
                }
            });
            return null;
        } catch (\Exception $e) {
            logger()->error('On-the-fly source fetch failed: ' . $e->getMessage(), [
                'anime' => $anime->id, 'episode' => $episode->id,
            ]);
            return $e->getMessage();
        }
    }

    // ─── API-like Actions (for AJAX) ────────────────────────────────────────────

    public function rate(Request $request, int $animeId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate(['score' => 'required|integer|min:1|max:10']);

        $rating = Rating::updateOrCreate(
            ['anime_id' => $animeId, 'user_id' => auth()->id()],
            ['score' => $request->score]
        );

        // Recalculate anime average
        $anime = Anime::findOrFail($animeId);
        $anime->update(['rating' => $anime->ratings()->avg('score')]);

        return response()->json(['score' => $rating->score, 'average' => $anime->rating]);
    }

    public function favorite(Request $request, int $animeId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $favorite = Favorite::firstOrNew([
            'anime_id' => $animeId,
            'user_id' => auth()->id()
        ]);

        if ($favorite->exists) {
            $favorite->delete();
            return response()->json(['favorited' => false]);
        }

        $favorite->save();
        return response()->json(['favorited' => true]);
    }

    public function comments(Request $request, int $animeId)
    {
        $comments = Comment::where('anime_id', $animeId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($comments);
    }

    public function postComment(Request $request, int $animeId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate(['body' => 'required|string|max:1000']);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'anime_id' => $animeId,
            'body' => $request->body,
            'is_spoiler' => $request->boolean('is_spoiler', false)
        ]);

        return response()->json($comment->load('user'));
    }

    // ─── Browse/Filter Routes ─────────────────────────────────────────────

    public function genre(string $slug)
    {
        $genre = Genre::where('slug', $slug)->firstOrFail();
        
        $anime = Anime::whereHas('genres', function ($q) use ($genre) {
            $q->where('genres.id', $genre->id);
        })
        ->orderBy('rating', 'desc')
        ->paginate(24);

        return view('anime.genre', compact('genre', 'anime'));
    }

    public function studio(string $slug)
    {
        $studio = Studio::where('slug', $slug)->firstOrFail();
        
        $anime = $studio->anime()
            ->orderBy('aired_year', 'desc')
            ->paginate(24);

        return view('anime.studio', compact('studio', 'anime'));
    }

    public function schedule()
    {
        $anime = Anime::where('status', 'ongoing')
            ->orderBy('title')
            ->with(['genres'])
            ->get()
            ->groupBy(fn($a) => $a->season ? $a->season . ' ' . $a->year : 'Unknown');

        return view('anime.schedule', compact('anime'));
    }

    public function popular()
    {
        $anime = Anime::orderBy('views_count', 'desc')
            ->paginate(24);

        return view('anime.popular', compact('anime'));
    }

    public function recentlyAdded()
    {
        $anime = Anime::orderBy('created_at', 'desc')
            ->paginate(24);

        return view('anime.recent', compact('anime'));
    }

    public function proxySource(Request $request, EpisodeSource $source)
    {
        $url = $source->url;
        $headers = $source->headers ?? [];

        if ($source->type === 'embed') {
            return redirect()->away($url);
        }

        $defaultHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];

        if (empty($headers['Referer']) && empty($headers['referer'])) {
            $parsed = parse_url($url);
            $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
            $defaultHeaders['Referer'] = $origin . '/';
        }

        $headers = array_merge($defaultHeaders, $headers);

        $client = Http::withHeaders($headers)->timeout(120);

        try {
            $range = $request->header('Range');
            $response = $range
                ? $client->withHeader('Range', $range)->get($url)
                : $client->get($url);

            if (!$response->ok() && $response->status() !== 206) {
                return response('', 502);
            }

            $contentType = $response->header('Content-Type');
            $contentLength = $response->header('Content-Length');
            $contentRange = $response->header('Content-Range');
            $body = $response->body();

            if (empty($body) && !$range) {
                return response('', 502);
            }
        } catch (\Exception $e) {
            return response('', 502);
        }

        $isM3u8 = $contentType && str_contains($contentType, 'mpegurl') || str_ends_with($url, '.m3u8');

        if ($isM3u8 && !$range) {
            $baseUrl = dirname($url);
            $proxySegmentUrl = route('proxy.segment', ['sourceId' => $source->id]);
            $body = preg_replace_callback(
                '/^(?!#)(.+\.(?:ts|m3u8|mp4|key))(.*)$/m',
                function ($m) use ($proxySegmentUrl) {
                    $cleaned = ltrim($m[1], './');
                    $rest = $m[2] ?? '';
                    return $proxySegmentUrl . '?path=' . urlencode($cleaned . $rest);
                },
                $body
            );
            $contentLength = null;
        }

        $respHeaders = array_filter([
            'Content-Type' => $contentType ?: 'video/mp4',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
            'Accept-Ranges' => 'bytes',
        ]);

        if ($contentLength) $respHeaders['Content-Length'] = $contentLength;
        if ($contentRange) $respHeaders['Content-Range'] = $contentRange;
        if ($range) $respHeaders['Content-Range'] = $contentRange ?: 'bytes 0-' . ($contentLength - 1) . '/' . $contentLength;

        $status = $range ? 206 : 200;

        return response($body, $status, $respHeaders);
    }

    private function resolveSegmentUrl(string $baseUrl, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $query = '';
        if (($pos = strpos($path, '?')) !== false) {
            $query = substr($path, $pos);
            $path = substr($path, 0, $pos);
        }
        if (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }
        if (str_starts_with($path, '/')) {
            $parsed = parse_url($baseUrl);
            $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
            return $origin . $path . $query;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/') . $query;
    }

    public function proxySegment(Request $request, $sourceId)
    {
        $source = EpisodeSource::findOrFail($sourceId);
        $path = $request->query('path');
        if (!$path) {
            abort(400, 'Missing path parameter');
        }

        $baseUrl = dirname($source->url);
        $segmentUrl = $this->resolveSegmentUrl($baseUrl, $path);

        $storedHeaders = $source->headers ?? [];
        $defaultHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => $baseUrl . '/',
        ];
        if (!empty($storedHeaders['Referer'])) {
            $defaultHeaders['Referer'] = $storedHeaders['Referer'];
        } elseif (!empty($storedHeaders['referer'])) {
            $defaultHeaders['Referer'] = $storedHeaders['referer'];
        }
        $headers = array_merge($defaultHeaders, $storedHeaders);

        $client = Http::withHeaders($headers)->timeout(60);
        $response = $client->get($segmentUrl);

        if (!$response->ok()) {
            abort(502, 'Segment fetch failed');
        }

        $contentType = $response->header('Content-Type');
        $body = $response->body();

        $isM3u8 = ($contentType && str_contains($contentType, 'mpegurl')) || str_ends_with($path, '.m3u8');

        // Recursively rewrite relative URLs inside sub-M3U8 playlists
        if ($isM3u8) {
            $proxySegmentUrl = route('proxy.segment', ['sourceId' => $source->id]);
            $pathDir = dirname($path);
            $pathPrefix = ($pathDir !== '.' && $pathDir !== '/') ? $pathDir . '/' : '';
            $body = preg_replace_callback(
                '/^(?!#)(.+\.(?:ts|m3u8|mp4|key))(.*)$/m',
                function ($m) use ($proxySegmentUrl, $pathPrefix) {
                    $matched = $m[1];
                    $rest = $m[2] ?? '';
                    if (str_starts_with($matched, 'http://') || str_starts_with($matched, 'https://') || str_starts_with($matched, '/')) {
                        $fullPath = $matched . $rest;
                    } else {
                        $fullPath = $pathPrefix . ltrim($matched, './') . $rest;
                    }
                    return $proxySegmentUrl . '?path=' . urlencode($fullPath);
                },
                $body
            );
            return response($body, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        $stream = $response->toPsrResponse()->getBody();

        return response()->stream(function () use ($stream) {
            while (!$stream->eof()) {
                echo $stream->read(8192);
                flush();
            }
        }, 200, [
            'Content-Type' => $contentType ?: 'video/MP2T',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
