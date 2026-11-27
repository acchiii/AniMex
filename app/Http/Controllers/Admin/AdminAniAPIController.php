<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\EpisodeSource;
use App\Models\Subtitle;
use App\Services\AnilistVideoSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAniAPIController extends Controller
{
    public function importEpisodeSources(Request $request, Anime $anime, Episode $episode)
    {
        abort_if($episode->anime_id !== $anime->id, 404);
        abort_if(! auth()->check() || ! auth()->user()->isAdmin(), 403);

        $anilistId = (int) ($anime->anilist_id ?? 0);
        abort_if($anilistId <= 0, 422, 'Anime does not have an AniList ID');

        /** @var AnilistVideoSourceService $service */
        $service = app(AnilistVideoSourceService::class);

        try {
            $result = $service->fetchEpisodeSources($anilistId, (int) $episode->number, $anime->title);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'Import failed: source lookup could not be completed.');
        }

        if (! empty($result['error'])) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'Import failed: '.$result['error']);
        }

        $sources = $result['sources'] ?? [];
        $subtitles = $result['subtitles'] ?? [];

        if (empty($sources)) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'No video sources found for this episode.');
        }

        DB::transaction(function () use ($episode, $sources, $subtitles) {
            EpisodeSource::where('episode_id', $episode->id)->delete();
            Subtitle::where('episode_id', $episode->id)->delete();

            $sort = 0;
            foreach ($sources as $s) {
                $quality = (string) ($s['quality'] ?? '720p');
                $url = (string) ($s['url'] ?? '');
                if ($url === '') {
                    continue;
                }

                EpisodeSource::create([
                    'episode_id' => $episode->id,
                    'video_server_id' => null,
                    'label' => (string) ($s['label'] ?? $quality),
                    'quality' => in_array($quality, ['360p', '480p', '720p', '1080p', '4K'], true) ? $quality : '720p',
                    'url' => $url,
                    'headers' => $s['headers'] ?? null,
                    'type' => (string) ($s['type'] ?? 'mp4'),
                    'language' => (string) ($s['language'] ?? 'sub'),
                    'is_active' => (bool) ($s['is_active'] ?? true),
                    'sort_order' => $sort++,
                ]);
            }

            foreach ($subtitles as $t) {
                $filePath = (string) ($t['file_path'] ?? '');
                if ($filePath === '') {
                    continue;
                }

                Subtitle::create([
                    'episode_id' => $episode->id,
                    'language' => (string) ($t['language'] ?? 'en'),
                    'label' => (string) ($t['label'] ?? strtoupper((string) ($t['language'] ?? 'en'))),
                    'file_path' => $filePath,
                    'is_default' => (bool) ($t['is_default'] ?? false),
                ]);
            }
        });

        $count = count($sources);

        return redirect()
            ->route('admin.anime.episodes', $anime)
            ->with('success', "Imported {$count} video source(s) successfully.");
    }

    public function importAllEpisodeSources(Request $request, Anime $anime)
    {
        abort_if(! auth()->check() || ! auth()->user()->isAdmin(), 403);

        $anilistId = (int) ($anime->anilist_id ?? 0);
        abort_if($anilistId <= 0, 422, 'Anime does not have an AniList ID');

        $remaining = $anime->episodes()->whereDoesntHave('sources')->count();
        $episodes = $anime->episodes()->whereDoesntHave('sources')
            ->orderBy('number')
            ->limit(60)
            ->get();

        if ($episodes->isEmpty()) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('info', 'All episodes already have sources.');
        }

        /** @var AnilistVideoSourceService $service */
        $service = app(AnilistVideoSourceService::class);

        try {
            $results = $service->fetchEpisodeSourcesBatch(
                $anilistId,
                $episodes->pluck('number')->all(),
                $anime->title_english ?: $anime->title
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'Import failed: source lookup could not be completed.');
        }

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($episodes, $results, &$imported, &$errors) {
            foreach ($episodes as $episode) {
                $result = $results[(int) $episode->number] ?? null;

                if (! $result || ! empty($result['error']) || empty($result['sources'])) {
                    $errors[] = "Ep {$episode->number}: ".($result['error'] ?? 'No sources');

                    continue;
                }

                $sort = 0;
                foreach ($result['sources'] as $source) {
                    $url = (string) ($source['url'] ?? '');
                    if ($url === '') {
                        continue;
                    }

                    EpisodeSource::create([
                        'episode_id' => $episode->id,
                        'video_server_id' => null,
                        'label' => (string) ($source['label'] ?? $source['quality'] ?? '720p'),
                        'quality' => in_array((string) ($source['quality'] ?? '720p'), ['360p', '480p', '720p', '1080p', '4K'], true) ? $source['quality'] : '720p',
                        'url' => $url,
                        'headers' => $source['headers'] ?? null,
                        'type' => (string) ($source['type'] ?? 'mp4'),
                        'language' => (string) ($source['language'] ?? 'sub'),
                        'is_active' => true,
                        'sort_order' => $sort++,
                    ]);
                }

                foreach ($result['subtitles'] ?? [] as $subtitle) {
                    $filePath = (string) ($subtitle['file_path'] ?? '');
                    if ($filePath === '') {
                        continue;
                    }

                    Subtitle::create([
                        'episode_id' => $episode->id,
                        'language' => (string) ($subtitle['language'] ?? 'en'),
                        'label' => (string) ($subtitle['label'] ?? strtoupper((string) ($subtitle['language'] ?? 'en'))),
                        'file_path' => $filePath,
                        'is_default' => (bool) ($subtitle['is_default'] ?? false),
                    ]);
                }

                $imported++;
            }
        });

        $message = "Imported sources for {$imported} episode(s).";
        if (! empty($errors)) {
            $message .= ' Errors: '.implode('; ', array_slice($errors, 0, 5));
        }
        if ($remaining > $episodes->count()) {
            $message .= ' Run again to import the remaining episodes.';
        }

        return redirect()
            ->route('admin.anime.episodes', $anime)
            ->with($errors ? 'warning' : 'success', $message);
    }

    public function fetchEpisodeList(Request $request, Anime $anime)
    {
        abort_if(! auth()->check() || ! auth()->user()->isAdmin(), 403);

        $anilistId = (int) ($anime->anilist_id ?? 0);
        abort_if($anilistId <= 0, 422, 'Anime does not have an AniList ID');

        /** @var AnilistVideoSourceService $service */
        $service = app(AnilistVideoSourceService::class);

        $result = $service->fetchEpisodeList($anilistId, $anime->title);

        if (! empty($result['error'])) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'Failed to fetch episode list: '.$result['error']);
        }

        $episodes = $result['episodes'] ?? [];

        $created = 0;
        $updated = 0;

        foreach ($episodes as $ep) {
            $number = (int) ($ep['number'] ?? 0);
            if ($number <= 0) {
                continue;
            }

            $existing = $anime->episodes()->where('number', $number)->first();

            $data = [
                'title' => ($ep['title'] ?? null) ?: "Episode {$number}",
                'synopsis' => $ep['description'] ?? null,
                'thumbnail' => $ep['image'] ?? null,
                'aired_at' => isset($ep['releaseDate']) && $ep['releaseDate'] ? $ep['releaseDate'] : null,
            ];

            if ($existing) {
                $dirty = false;
                if (! $existing->title && $data['title']) {
                    $existing->title = $data['title'];
                    $dirty = true;
                }
                if (! $existing->synopsis && $data['synopsis']) {
                    $existing->synopsis = $data['synopsis'];
                    $dirty = true;
                }
                if (! $existing->thumbnail && $data['thumbnail']) {
                    $existing->thumbnail = $data['thumbnail'];
                    $dirty = true;
                }
                if (! $existing->aired_at && $data['aired_at']) {
                    $existing->aired_at = $data['aired_at'];
                    $dirty = true;
                }
                if ($dirty) {
                    $existing->save();
                    $updated++;
                }
            } else {
                $episode = new Episode;
                $episode->anime_id = $anime->id;
                $episode->number = $number;
                $episode->title = $data['title'];
                $episode->synopsis = $data['synopsis'];
                $episode->thumbnail = $data['thumbnail'];
                $episode->aired_at = $data['aired_at'];
                $episode->save();
                $created++;
            }
        }

        $total = count($episodes);
        $parts = [];
        if ($created > 0) {
            $parts[] = "{$created} created";
        }
        if ($updated > 0) {
            $parts[] = "{$updated} updated";
        }
        $parts[] = "{$total} total from provider ({$result['provider']})";

        return redirect()
            ->route('admin.anime.episodes', $anime)
            ->with('success', 'Episodes synced: '.implode(', ', $parts).'.');
    }
}
