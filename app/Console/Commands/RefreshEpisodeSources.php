<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\EpisodeSource;
use App\Models\Subtitle;
use App\Services\AnilistVideoSourceService;
use App\Services\AnivexaVideoSourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefreshEpisodeSources extends Command
{
    protected $signature = 'animex:refresh-episode-sources {--anilist=} {--episode=} {--all} {--dry-run}';

    protected $description = 'Replace stored episode sources with the configured Anivexa providers';

    public function handle(AnilistVideoSourceService $service, AnivexaVideoSourceService $anivexa): int
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

        $query->chunkById(10, function ($animes) use ($service, $numbers, $dryRun, &$updated, &$failed): void {
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
                        DB::transaction(function () use ($episode, $sources, $result): void {
                            EpisodeSource::query()->where('episode_id', $episode->id)->delete();
                            Subtitle::query()->where('episode_id', $episode->id)->delete();

                            $sort = 0;
                            foreach ($sources as $source) {
                                $quality = (string) ($source['quality'] ?? '720p');
                                $url = (string) ($source['url'] ?? '');
                                if ($url === '') {
                                    continue;
                                }

                                EpisodeSource::create([
                                    'episode_id' => $episode->id,
                                    'video_server_id' => null,
                                    'label' => (string) ($source['label'] ?? $quality),
                                    'quality' => in_array($quality, ['360p', '480p', '720p', '1080p', '4K'], true) ? $quality : '720p',
                                    'url' => $url,
                                    'headers' => $source['headers'] ?? null,
                                    'type' => (string) ($source['type'] ?? 'mp4'),
                                    'language' => (string) ($source['language'] ?? 'sub'),
                                    'is_active' => (bool) ($source['is_active'] ?? true),
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
                        });
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
