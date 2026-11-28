<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\Episode;
use App\Services\AnilistVideoSourceService;
use App\Services\AnivexaVideoSourceService;
use App\Services\EpisodeSourceStoreService;
use Illuminate\Console\Command;
use Throwable;

class RefreshEpisodeSources extends Command
{
    protected $signature = 'animex:refresh-episode-sources {--anilist=} {--episode=} {--all} {--dry-run}';

    protected $description = 'Replace stored episode sources with the configured Anivexa providers';

    public function handle(AnilistVideoSourceService $service, AnivexaVideoSourceService $anivexa, EpisodeSourceStoreService $store): int
    {
        if (! $anivexa->isEnabled()) {
            $this->error('Anivexa is not configured. Set ANIVEXA_BASE_URL first.');

            return self::FAILURE;
        }

        if (! $this->option('all') && ! $this->option('anilist')) {
            $this->error('Pass --anilist=ID or --all to choose which anime to refresh.');

            return self::FAILURE;
        }

        $query = Anime::query()->whereNotNull('anilist_id');
        if ($anilistId = $this->option('anilist')) {
            $query->where('anilist_id', (int) $anilistId);
        }

        $numbers = array_values(array_unique(array_filter(array_map(
            'intval',
            explode(',', (string) $this->option('episode'))
        ))));
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $failed = 0;

        $query->chunkById(10, function ($animes) use ($service, $store, $numbers, $dryRun, &$updated, &$failed): void {
            foreach ($animes as $anime) {
                $episodeQuery = Episode::query()->where('anime_id', $anime->id);
                if ($numbers) {
                    $episodeQuery->whereIn('number', $numbers);
                }

                $episodes = $episodeQuery->orderBy('number')->get(['id', 'number']);
                if ($episodes->isEmpty()) {
                    $failed++;

                    $this->warn("Anime {$anime->id}: no episodes matched");

                    continue;
                }

                try {
                    $results = $service->fetchEpisodeSourcesBatch(
                        (int) $anime->anilist_id,
                        $episodes->pluck('number')->map(fn ($number) => (int) $number)->all(),
                        $anime->title
                    );
                } catch (Throwable $e) {
                    $failed++;

                    $this->warn("Anime {$anime->id}: {$e->getMessage()}");

                    continue;
                }

                foreach ($episodes as $episode) {
                    $result = $results[(int) $episode->number] ?? ['error' => 'No source result'];
                    $sources = $result['sources'] ?? [];
                    if (empty($sources)) {
                        $failed++;

                        $this->warn("Episode {$episode->id}: {$result['error']}");

                        continue;
                    }

                    if (! $dryRun) {
                        $store->store($episode, $result);
                    }

                    $updated++;
                }
            }
        });

        $verb = $dryRun ? 'would update' : 'updated';

        $this->info("Anivexa {$verb} {$updated} episode source sets; {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
