<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\EpisodeSource;
use App\Models\Subtitle;
use App\Services\AnilistVideoSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAniAPIController extends Controller
{
    public function importEpisodeSources(Request $request, Anime $anime, Episode $episode)
    {
        abort_if($episode->anime_id !== $anime->id, 404);

        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $anilistId = (int) ($anime->anilist_id ?? 0);
        abort_if($anilistId <= 0, 422, 'Anime does not have an AniList ID');

        /** @var AnilistVideoSourceService $service */
        $service = app(AnilistVideoSourceService::class);

            $result = $service->fetchEpisodeSources($anilistId, (int) $episode->number, $anime->title);

        if (!empty($result['error'])) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'Import failed: ' . $result['error']);
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
                if ($url === '') continue;

                EpisodeSource::create([
                    'episode_id' => $episode->id,
                    'video_server_id' => null,
                    'label' => (string) ($s['label'] ?? $quality),
                    'quality' => in_array($quality, ['360p', '480p', '720p', '1080p', '4K'], true) ? $quality : '720p',
                    'url' => $url,
                    'headers' => $s['headers'] ?? null,
                    'type' => (string) ($s['type'] ?? 'hls'),
                    'language' => (string) ($s['language'] ?? 'sub'),
                    'is_active' => (bool) ($s['is_active'] ?? true),
                    'sort_order' => $sort++,
                ]);
            }

            foreach ($subtitles as $t) {
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

        $count = count($sources);
        return redirect()
            ->route('admin.anime.episodes', $anime)
            ->with('success', "Imported {$count} video source(s) successfully.");
    }

    public function importAllEpisodeSources(Request $request, Anime $anime)
    {
        abort_if(!auth()->check() || !auth()->user()->hasRole('admin'), 403);

        $anilistId = (int) ($anime->anilist_id ?? 0);
        abort_if($anilistId <= 0, 422, 'Anime does not have an AniList ID');

        $episodes = $anime->episodes()->whereDoesntHave('sources')->get();

        if ($episodes->isEmpty()) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('info', 'All episodes already have sources.');
        }

        /** @var AnilistVideoSourceService $service */
        $service = app(AnilistVideoSourceService::class);

        $imported = 0;
        $errors = [];

        foreach ($episodes as $episode) {
            $result = $service->fetchEpisodeSources($anilistId, (int) $episode->number, $anime->title);

            if (!empty($result['error']) || empty($result['sources'])) {
                $errors[] = "Ep {$episode->number}: " . ($result['error'] ?? 'No sources');
                continue;
            }

            DB::transaction(function () use ($episode, $result, &$imported) {
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

                $imported++;
            });

            usleep(500000);
        }

        $message = "Imported sources for {$imported} episode(s).";
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode('; ', array_slice($errors, 0, 5));
        }

        return redirect()
            ->route('admin.anime.episodes', $anime)
            ->with($errors ? 'warning' : 'success', $message);
    }
}
