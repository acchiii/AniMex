<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\Episode;
use App\Services\AnivexaVideoSourceService;
use Illuminate\Console\Command;
use Throwable;

class RefreshEpisodeTitles extends Command
{
    protected $signature = 'animex:refresh-episode-titles {--anilist=} {--dry-run}';

    protected $description = 'Replace stored episode titles with Anivexa titles';

    public function handle(AnivexaVideoSourceService $service): int
    {
        if (! $service->isEnabled()) {
            $this->error('Anivexa is not configured. Set ANIVEXA_BASE_URL first.');

            return self::FAILURE;
        }

        $query = Anime::query()->whereNotNull('anilist_id');
        $anilistId = $this->option('anilist');
        if ($anilistId) {
            $query->where('anilist_id', (int) $anilistId);
        }

        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $failed = 0;

        $query->chunkById(50, function ($animes) use ($service, $dryRun, &$updated, &$failed): void {
            foreach ($animes as $anime) {
                try {
                    $result = $service->fetchEpisodeList((int) $anime->anilist_id);
                } catch (Throwable $e) {
                    $failed++;
                    $this->warn("Anime {$anime->id}: {$e->getMessage()}");

                    continue;
                }

                if (! empty($result['error']) || empty($result['episodes'])) {
                    $failed++;
                    $this->warn("Anime {$anime->id}: {$result['error']}");

                    continue;
                }

                $titles = [];
                foreach ($result['episodes'] as $episode) {
                    $number = (int) ($episode['number'] ?? 0);
                    $title = trim((string) ($episode['title'] ?? ''));
                    if ($number > 0 && $title !== '') {
                        $titles[$number] = $title;
                    }
                }

                foreach ($titles as $number => $title) {
                    $existing = Episode::where('anime_id', $anime->id)
                        ->where('number', $number)
                        ->first();

                    if (! $existing || $existing->title === $title) {
                        continue;
                    }

                    if (! $dryRun) {
                        $existing->title = $title;
                        $existing->save();
                    }

                    $updated++;
                }
            }
        });

        $verb = $dryRun ? 'would update' : 'updated';

        $this->info("Anivexa {$verb} {$updated} episode titles; {$failed} anime failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
