<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Services\AnilistVideoSourceService;
use App\Services\EpisodeSourceStoreService;
use Illuminate\Http\Request;

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

        if (empty($sources)) {
            return redirect()
                ->route('admin.anime.episodes', $anime)
                ->with('error', 'No video sources found for this episode.');
        }

        app(EpisodeSourceStoreService::class)->store($episode, $result);

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

        foreach ($episodes as $episode) {
            $result = $results[(int) $episode->number] ?? null;

            if (! $result || ! empty($result['error']) || empty($result['sources'])) {
                $errors[] = "Ep {$episode->number}: ".($result['error'] ?? 'No sources');

                continue;
            }

            app(EpisodeSourceStoreService::class)->store($episode, $result);
            $imported++;
        }

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

        $existing = Episode::withTrashed()
            ->where('anime_id', $anime->id)
            ->get()
            ->keyBy('number');
        $queued = [];
        $inserts = [];
        $updates = [];
        $timestamp = now()->format('Y-m-d H:i:s');

        foreach ($episodes as $ep) {
            $number = (int) ($ep['number'] ?? 0);
            if ($number <= 0 || isset($queued[$number])) {
                continue;
            }
            $queued[$number] = true;

            $data = [
                'title' => ($ep['title'] ?? null) ?: "Episode {$number}",
                'synopsis' => $ep['description'] ?? null,
                'thumbnail' => $ep['image'] ?? null,
                'aired_at' => ! empty($ep['releaseDate']) ? substr((string) $ep['releaseDate'], 0, 10) : null,
            ];

            $current = $existing->get($number);
            if (! $current) {
                $inserts[] = array_merge([
                    'anime_id' => $anime->id,
                    'number' => $number,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ], $data);

                continue;
            }

            if ($current->trashed()) {
                continue;
            }

            $changes = [];
            foreach (['title', 'synopsis', 'thumbnail', 'aired_at'] as $field) {
                if (! $current->{$field} && $data[$field]) {
                    $changes[$field] = $data[$field];
                }
            }
            if (! empty($changes)) {
                $changes['updated_at'] = $timestamp;
                $updates[$current->id] = $changes;
            }
        }

        if (! empty($inserts)) {
            Episode::insertOrIgnore($inserts);
        }
        foreach ($updates as $id => $changes) {
            Episode::where('id', $id)->update($changes);
        }

        $created = count($inserts);
        $updated = count($updates);
        $total = count($episodes);
        $provider = (string) ($result['provider'] ?? 'unknown');
        $sourceLabel = strtolower($provider) === 'anilist'
            ? 'AniList metadata (stream sources may be unavailable)'
            : "provider ({$provider})";
        $parts = [];
        if ($created > 0) {
            $parts[] = "{$created} created";
        }
        if ($updated > 0) {
            $parts[] = "{$updated} updated";
        }
        $parts[] = "{$total} total from {$sourceLabel}";

        return redirect()
            ->route('admin.anime.episodes', $anime)
            ->with('success', 'Episodes synced: '.implode(', ', $parts).'.');
    }
}
