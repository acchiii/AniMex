<?php

namespace App\Http\Controllers;

use App\Models\AdPosition;
use App\Models\Anime;
use App\Models\Comment;
use App\Models\Episode;
use App\Models\EpisodeSource;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Rating;
use App\Models\Studio;
use App\Models\Subtitle;
use App\Models\WatchHistory;
use App\Services\AnilistVideoSourceService;
use App\Services\JimakuService;
use App\Services\OpenSubtitlesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Helper function for auth check */
function user_check(): bool
{
    return Auth::check();
}

/** Helper function to get user id */
function user_id(): ?int
{
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
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('synopsis', 'like', '%'.$request->search.'%');
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
                $q->orderBy('number')->withCount('sources');
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

        $episode = Episode::where('anime_id', $anime->id)
            ->where('number', $episodeNumber)
            ->with(['sources' => function ($q) {
                $q->orderByDesc('quality');
            }, 'subtitles'])
            ->firstOrFail();

        $sourcesPending = $episode->sources->isEmpty();

        // All episodes for the episode list
        $episodes = $anime->episodes()->orderBy('number')->get();

        // Next/Previous episodes
        $nextEpisode = $episodes->firstWhere('number', '>', $episodeNumber);
        $prevEpisode = $episodes->firstWhere('number', '<', $episodeNumber);

        // Update watch history
        if (auth()->check()) {
            $userId = auth()->id();
            WatchHistory::updateOrCreate(
                ['user_id' => $userId, 'episode_id' => $episode->id],
                ['anime_id' => $anime->id, 'watched_at' => now()]
            );
        }

        try {
            // Fetch English subtitles — always try Jimaku first (replaces mislabeled provider subs)
            // 1. Try Jimaku (by AniList ID) — more accurate/synchronized
            $hasCachedEnglish = $episode->subtitles()
                ->where('language', 'en')
                ->where('file_path', 'like', '%/subtitles/%')
                ->exists();
            $jimakuEnglishFound = $hasCachedEnglish;
            if (! $hasCachedEnglish && $anime->anilist_id) {
                $jimaku = app(JimakuService::class);
                $subs = $jimaku->fetchSubtitles(
                    (int) $anime->anilist_id,
                    $episodeNumber,
                    'en'
                );
                if (! empty($subs)) {
                    $episode->subtitles()->where('language', 'en')->delete();
                    foreach ($subs as $sub) {
                        Subtitle::create(['episode_id' => $episode->id] + $sub);
                    }
                    if ($subs[0]['language'] === 'en') {
                        $jimakuEnglishFound = true;
                    }
                }
            }

            // 2. Fall back to OpenSubtitles if Jimaku didn't find English subs
            if (! $jimakuEnglishFound && ! $hasCachedEnglish) {
                $episode->subtitles()->where('language', 'en')->delete();
                $os = app(OpenSubtitlesService::class);
                $subs = $os->fetchSubtitles(
                    $anime->title_english ?: $anime->title,
                    $os->guessSeason($episodeNumber),
                    $episodeNumber,
                    'en'
                );
                if (! empty($subs)) {
                    foreach ($subs as $sub) {
                        Subtitle::create(['episode_id' => $episode->id] + $sub);
                    }
                }
            }

            $episode->load('subtitles');
        } catch (\Throwable $e) {
            $this->safeLog('warning', 'Subtitle retrieval failed', [
                'anime' => $anime->id, 'episode' => $episode->id, 'error' => $e->getMessage(),
            ]);
        }

        // Ads
        $bannerAd = null;
        $user = auth()->user();
        if (! $user || ! $user->isPremium()) {
            $position = AdPosition::where('key', 'episode_overlay')->first();
            if ($position) {
                $bannerAd = $position->getActiveAd($user);
            }
        }

        return view('anime.stream', compact('episode', 'anime', 'episodes', 'nextEpisode', 'prevEpisode', 'bannerAd', 'sourcesPending'));
    }

    public function sources(Anime $anime, Episode $episode)
    {
        abort_if($episode->anime_id !== $anime->id, 404);

        return response()->json($this->fetchSourcesOnTheFly($anime, $episode));
    }

    private function fetchSourcesOnTheFly($anime, $episode): array
    {
        try {
            $lock = Cache::lock("sources:fetch:{$episode->id}", 60);
            if (! $lock->get()) {
                return ['status' => 'pending'];
            }

            try {
                if ($episode->sources()->exists()) {
                    return ['status' => 'ready'];
                }

                $service = app(AnilistVideoSourceService::class);
                $title = $anime->title_english ?: $anime->title;
                $result = $service->fetchEpisodeSources(
                    (int) ($anime->anilist_id ?? 0), (int) $episode->number, $title
                );

                if (! empty($result['error']) || empty($result['sources'])) {
                    $error = $result['error'] ?? 'All providers returned no sources';
                    $this->safeLog('warning', 'On-the-fly source fetch failed', [
                        'anime' => $anime->id, 'episode' => $episode->id, 'title' => $title,
                        'error' => $error,
                    ]);

                    return ['status' => 'error', 'error' => $error];
                }

                $this->storeSources($episode, $result);

                return ['status' => 'ready'];
            } finally {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    $this->safeLog('warning', 'Source fetch lock release failed', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            $this->safeLog('error', 'Source fetch lock failed', [
                'anime' => $anime->id, 'episode' => $episode->id, 'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'error' => 'Could not check sources right now. Please try again.'];
        }
    }

    private function storeSources($episode, array $result): void
    {
        DB::transaction(function () use ($episode, $result) {
            $sort = 0;
            foreach ($result['sources'] as $source) {
                $url = (string) ($source['url'] ?? '');
                if ($url === '' || ! $this->isValidSourceUrl($url)) {
                    continue;
                }

                EpisodeSource::create([
                    'episode_id' => $episode->id,
                    'video_server_id' => null,
                    'label' => (string) ($source['label'] ?? $source['quality'] ?? '720p'),
                    'quality' => in_array((string) ($source['quality'] ?? '720p'), ['360p', '480p', '720p', '1080p', '4K'], true) ? $source['quality'] : '720p',
                    'url' => $url,
                    'headers' => $source['headers'] ?? null,
                    'type' => (string) ($source['type'] ?? 'hls'),
                    'language' => (string) ($source['language'] ?? 'sub'),
                    'is_active' => true,
                    'sort_order' => $sort++,
                ]);
            }

            foreach ($result['subtitles'] ?? [] as $subtitle) {
                $filePath = (string) ($subtitle['file_path'] ?? '');
                $language = (string) ($subtitle['language'] ?? 'en');
                if ($filePath === '' || ! in_array($language, ['en', 'ja'], true)) {
                    continue;
                }

                Subtitle::create([
                    'episode_id' => $episode->id,
                    'language' => $language,
                    'label' => (string) ($subtitle['label'] ?? strtoupper($language)),
                    'file_path' => $filePath,
                    'is_default' => $language === 'en',
                ]);
            }
        });
    }

    // ─── API-like Actions (for AJAX) ────────────────────────────────────────────

    public function rate(Request $request, int $animeId)
    {
        if (! auth()->check()) {
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
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $favorite = Favorite::firstOrNew([
            'anime_id' => $animeId,
            'user_id' => auth()->id(),
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
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate(['body' => 'required|string|max:1000']);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'anime_id' => $animeId,
            'body' => $request->body,
            'is_spoiler' => $request->boolean('is_spoiler', false),
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
            ->groupBy(fn ($a) => $a->season ? $a->season.' '.$a->year : 'Unknown');

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

    public function saveProgress(Request $request)
    {
        $data = $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'progress' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0',
        ]);

        if ($user = Auth::user()) {
            WatchHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'episode_id' => $data['episode_id'],
                ],
                [
                    'anime_id' => Episode::find($data['episode_id'])?->anime_id,
                    'progress' => $data['progress'],
                    'duration' => $data['duration'],
                    'completed' => $data['progress'] > 0 && $data['duration'] > 0 && $data['progress'] >= $data['duration'] * 0.9,
                    'watched_at' => now(),
                ]
            );
        }

        return response('', 204);
    }

    public function proxySource(Request $request, EpisodeSource $source, ?string $path = null)
    {
        @set_time_limit(120);

        $baseUrl = dirname($source->url);
        $url = $path ? $this->resolveSegmentUrl($baseUrl, $path) : $source->url;
        $headers = $source->headers ?? [];

        if (! $this->isValidSourceUrl($url)) {
            return response('Invalid source URL', 502);
        }

        if ($source->type === 'embed' && ! $path) {
            return redirect()->away($url);
        }

        $defaultHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
        ];

        if (empty($headers['Referer']) && empty($headers['referer'])) {
            $parsed = parse_url($url);
            $origin = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
            $defaultHeaders['Referer'] = $origin.'/';
        }

        $headers = array_merge($defaultHeaders, $headers);
        $client = Http::withHeaders($headers)->timeout(30);

        try {
            $range = $request->header('Range');

            $psrResponse = $client->send('GET', $url, [
                'headers' => $range ? ['Range' => $range] : [],
                'stream' => true,
            ])->toPsrResponse();

            $status = $psrResponse->getStatusCode();
            if ($status < 200 || $status >= 300) {
                if ($status !== 206) {
                    return response('', 502);
                }
            }

            $contentType = $psrResponse->getHeaderLine('Content-Type');
            $contentLength = $psrResponse->getHeaderLine('Content-Length');
            $contentRange = $psrResponse->getHeaderLine('Content-Range');
            $bodyStream = $psrResponse->getBody();

            $isM3u8 = ($contentType && str_contains($contentType, 'mpegurl')) || str_ends_with($url, '.m3u8');

            if ($isM3u8 && ! $range) {
                $body = $bodyStream->getContents();
                if (empty($body)) {
                    return response('', 502);
                }
                $proxySegmentUrl = route('proxy.segment', ['sourceId' => $source->id]);
                $body = $this->rewriteM3u8Urls($body, $proxySegmentUrl, $url);
                $contentLength = null;

                $respHeaders = array_filter([
                    'Content-Type' => 'application/vnd.apple.mpegurl',
                    'Cache-Control' => 'public, max-age=3600',
                    'Access-Control-Allow-Origin' => '*',
                    'Accept-Ranges' => 'bytes',
                ]);

                return response($body, 200, $respHeaders);
            }

            $respHeaders = array_filter([
                'Content-Type' => $contentType ?: 'video/mp4',
                'Cache-Control' => 'public, max-age=3600',
                'Access-Control-Allow-Origin' => '*',
                'Accept-Ranges' => 'bytes',
            ]);
            if ($contentLength) {
                $respHeaders['Content-Length'] = $contentLength;
            }
            if ($contentRange) {
                $respHeaders['Content-Range'] = $contentRange;
            }

            $responseStatus = $range ? 206 : 200;

            return response()->stream(function () use ($bodyStream) {
                while (! $bodyStream->eof()) {
                    echo $bodyStream->read(8192);
                    flush();
                }
                $bodyStream->close();
            }, $responseStatus, $respHeaders);
        } catch (\Exception $e) {
            return response('', 502);
        }
    }

    private function proxiedSegmentUrl(string $proxySegmentUrl, string $uri): string
    {
        if (str_contains($uri, '/proxy/segment/')) {
            return $uri;
        }

        return $proxySegmentUrl.'?path='.urlencode($uri);
    }

    private function rewriteM3u8Urls(string $body, string $proxySegmentUrl, string $originalUrl): string
    {
        $baseUrl = dirname($originalUrl);
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $body = preg_replace_callback(
            '/^(?!#)(\S+)/m',
            function ($m) use ($proxySegmentUrl, $baseUrl) {
                $line = trim($m[1]);
                if ($line === '') {
                    return $m[0];
                }

                $path = $this->resolveSegmentUrl($baseUrl, $line);

                return $this->proxiedSegmentUrl($proxySegmentUrl, $path);
            },
            $body
        );

        $body = preg_replace_callback(
            '/\bURI="([^"]+)"/i',
            function ($m) use ($proxySegmentUrl, $baseUrl) {
                $path = $this->resolveSegmentUrl($baseUrl, $m[1]);

                return 'URI="'.$this->proxiedSegmentUrl($proxySegmentUrl, $path).'"';
            },
            $body
        );

        $body = preg_replace_callback(
            '/\bURI=([^\s,"\']+)/i',
            function ($m) use ($proxySegmentUrl, $baseUrl) {
                $path = $this->resolveSegmentUrl($baseUrl, $m[1]);

                return 'URI='.$this->proxiedSegmentUrl($proxySegmentUrl, $path);
            },
            $body
        );

        return $body;
    }

    private function isValidSourceUrl(string $url): bool
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return false;
        }

        if (preg_match('/\.(replace|replaceAll|toString|split|join|concat)\(/', $url)) {
            logger()->warning('Filtered out malformed source URL', ['url' => $url]);

            return false;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    private function resolveSegmentUrl(string $baseUrl, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme.':'.$path;
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
            $origin = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');

            return $origin.$path.$query;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/').$query;
    }

    private function normalizeSegmentUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (! is_array($parsed) || empty($parsed['path'])) {
            return $url;
        }

        if (! preg_match('#/seg-\d+-[^/]+\.(?:jpe?g|html?|js|css|txt|png|webp|gif)$#i', $parsed['path'])) {
            return $url;
        }

        $parsed['path'] = (string) preg_replace('/\.[^.\/]+$/', '.ts', $parsed['path']);

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'].'://' : '';
        $host = (string) ($parsed['host'] ?? '');
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.$host.$port.$parsed['path'].$query.$fragment;
    }

    public function proxySegment(Request $request, $sourceId)
    {
        @set_time_limit(240);

        $source = EpisodeSource::findOrFail($sourceId);
        $path = $request->query('path');
        if (! $path) {
            abort(400, 'Missing path parameter');
        }

        $baseUrl = dirname($source->url);
        $segmentUrl = $this->resolveSegmentUrl($baseUrl, $path);
        $fetchUrls = array_values(array_unique([$this->normalizeSegmentUrl($segmentUrl), $segmentUrl]));

        $storedHeaders = $source->headers ?? [];
        $defaultHeaders = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'Accept' => '*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Referer' => $baseUrl.'/',
        ];
        if (! empty($storedHeaders['Referer'])) {
            $defaultHeaders['Referer'] = $storedHeaders['Referer'];
        } elseif (! empty($storedHeaders['referer'])) {
            $defaultHeaders['Referer'] = $storedHeaders['referer'];
        }
        $headers = array_merge($defaultHeaders, $storedHeaders);

        try {
            $psrResponse = null;
            $lastError = 'Segment fetch failed';

            foreach ($fetchUrls as $fetchUrl) {
                try {
                    $candidate = Http::withHeaders($headers)
                        ->connectTimeout(10)
                        ->timeout(120)
                        ->send('GET', $fetchUrl, ['stream' => true])
                        ->toPsrResponse();

                    $status = $candidate->getStatusCode();
                    if ($status >= 200 && $status < 300) {
                        $psrResponse = $candidate;

                        break;
                    }

                    $lastError = 'Segment fetch failed with status '.$status;
                    $candidate->getBody()->close();
                } catch (\Throwable $e) {
                    $lastError = 'Segment fetch error: '.$e->getMessage();
                }
            }

            if (! $psrResponse) {
                abort(502, $lastError);
            }

            $contentType = $psrResponse->getHeaderLine('Content-Type');
            $bodyStream = $psrResponse->getBody();

            $isM3u8 = ($contentType && str_contains($contentType, 'mpegurl')) || str_ends_with($path, '.m3u8');

            if ($isM3u8) {
                $body = $bodyStream->getContents();
                if (empty($body)) {
                    abort(502, 'Empty playlist response');
                }
                $proxySegmentUrl = route('proxy.segment', ['sourceId' => $source->id]);
                $body = $this->rewriteM3u8UrlsForSubPlaylist($body, $proxySegmentUrl, $path, $baseUrl);

                return response($body, 200, [
                    'Content-Type' => 'application/vnd.apple.mpegurl',
                    'Access-Control-Allow-Origin' => '*',
                    'Cache-Control' => 'public, max-age=3600',
                ]);
            }

            if ($contentType) {
                $lower = strtolower($contentType);
                if (str_contains($lower, 'text/html') || str_contains($lower, 'text/plain')) {
                    abort(502, 'Invalid segment content type: '.$contentType);
                }
            }

            $respHeaders = [
                'Content-Type' => $contentType ?: 'video/MP2T',
                'Cache-Control' => 'public, max-age=3600',
                'Access-Control-Allow-Origin' => '*',
            ];

            return response()->stream(function () use ($bodyStream) {
                while (! $bodyStream->eof()) {
                    echo $bodyStream->read(8192);
                    flush();
                }
                $bodyStream->close();
            }, 200, $respHeaders);
        } catch (\Exception $e) {
            abort(502, 'Segment fetch error: '.$e->getMessage());
        }
    }

    private function rewriteM3u8UrlsForSubPlaylist(string $body, string $proxySegmentUrl, string $currentPath, string $baseUrl): string
    {
        $pathDir = dirname($currentPath);
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        $resolveRelative = function (string $uri) use ($pathDir, $baseUrl): string {
            if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
                return $uri;
            }

            if (str_starts_with($uri, '//')) {
                $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

                return $scheme.':'.$uri;
            }

            if (str_starts_with($uri, '/')) {
                $parsed = parse_url($baseUrl);
                $origin = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');

                return $origin.$uri;
            }

            $prefix = ($pathDir && $pathDir !== '.' && $pathDir !== '/') ? $pathDir.'/' : '';

            return $prefix.ltrim($uri, './');
        };

        $body = preg_replace_callback(
            '/^(?!#)(\S+)/m',
            function ($m) use ($proxySegmentUrl, $resolveRelative) {
                $line = trim($m[1]);
                if ($line === '') {
                    return $m[0];
                }

                return $this->proxiedSegmentUrl($proxySegmentUrl, $resolveRelative($line));
            },
            $body
        );

        $body = preg_replace_callback(
            '/\bURI="([^"]+)"/i',
            function ($m) use ($proxySegmentUrl, $resolveRelative) {
                return 'URI="'.$this->proxiedSegmentUrl($proxySegmentUrl, $resolveRelative($m[1])).'"';
            },
            $body
        );

        $body = preg_replace_callback(
            '/\bURI=([^\s,"\']+)/i',
            function ($m) use ($proxySegmentUrl, $resolveRelative) {
                return 'URI='.$this->proxiedSegmentUrl($proxySegmentUrl, $resolveRelative($m[1]));
            },
            $body
        );

        return $body;
    }

    public function proxySubtitle(Request $request)
    {
        $url = $request->query('url');
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Invalid subtitle URL');
        }

        try {
            $referer = null;
            $source = EpisodeSource::find($request->query('source'));
            if ($source && is_array($source->headers)) {
                $referer = $source->headers['Referer'] ?? $source->headers['referer'] ?? null;
            }

            if (! $referer) {
                $parsed = parse_url($url);
                $referer = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').'/';
            }

            $headers = [
                'Referer' => $referer,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ];

            $resp = Http::withHeaders($headers)
                ->timeout(30)
                ->get($url);

            if (! $resp->successful()) {
                abort(502, 'Failed to fetch subtitle');
            }

            $content = $resp->body();
            $mime = 'text/vtt; charset=utf-8';

            return response($content, 200, [
                'Content-Type' => $mime,
                'Content-Length' => strlen($content),
                'Cache-Control' => 'public, max-age=86400',
                'Access-Control-Allow-Origin' => '*',
            ]);
        } catch (\Exception $e) {
            abort(502, 'Subtitle proxy error: '.$e->getMessage());
        }
    }

    public function serveSubtitle(string $filename)
    {
        $path = storage_path("app/public/subtitles/{$filename}");

        if (! file_exists($path)) {
            abort(404);
        }

        $content = file_get_contents($path);
        $mime = str_ends_with($filename, '.vtt') ? 'text/vtt' : 'text/plain; charset=utf-8';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (\Throwable $e) {
            return;
        }
    }
}
