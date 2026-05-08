<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\EpisodeSource;
use App\Models\Subtitle;
use App\Services\AnilistVideoSourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAnimeSources extends Command
{
    protected $signature = 'anime:import-sources
                            {anime? : The anime ID or slug}
                            {--all : Import sources for all anime with anilist_id}
                            {--episode= : Only import a specific episode number}';

    protected $description = 'Import video sources for anime episodes from Consumet API';

    public function handle(AnilistVideoSourceService $service): int
    {
        $anime = null;

        if ($animeInput = $this->argument('anime')) {
            $anime = is_numeric($animeInput)
                ? Anime::find((int) $animeInput)
                : Anime::where('slug', $animeInput)->first();

            if (!$anime) {
                $this->error("Anime not found: {$animeInput}");
                return self::FAILURE;
            }
        }

        if ($this->option('all')) {
            $animes = Anime::whereNotNull('anilist_id')->where('anilist_id', '>', 0)->get();
        } elseif ($anime) {
            $animes = collect([$anime]);
        } else {
            $this->error('Specify an anime ID/slug or use --all');
            return self::FAILURE;
        }

        foreach ($animes as $a) {
            $this->info("Processing: {$a->title} (AniList ID: {$a->anilist_id})");

            $episodes = $a->episodes()->orderBy('number')->get();
            if ($this->option('episode')) {
                $episodes = $episodes->where('number', (int) $this->option('episode'));
            }

            $bar = $this->output->createProgressBar($episodes->count());
            $bar->start();

            $success = 0;
            $failed = 0;

            foreach ($episodes as $episode) {
                $result = $service->fetchEpisodeSources((int) $a->anilist_id, (int) $episode->number, $a->title);

                if (!empty($result['error']) || empty($result['sources'])) {
                    $failed++;
                    $bar->advance();
                    continue;
                }

                DB::transaction(function () use ($episode, $result) {
                    EpisodeSource::where('episode_id', $episode->id)->delete();
                    Subtitle::where('episode_id', $episode->id)->delete();

                    foreach ($result['sources'] as $i => $s) {
                        if (empty($s['url'])) continue;
                        EpisodeSource::create([
                            'episode_id' => $episode->id,
                            'label' => $s['label'] ?? $s['quality'] ?? '720p',
                            'quality' => $s['quality'] ?? '720p',
                            'url' => $s['url'],
                            'headers' => $s['headers'] ?? null,
                            'type' => $s['type'] ?? 'hls',
                            'language' => $s['language'] ?? 'sub',
                            'is_active' => true,
                            'sort_order' => $i,
                        ]);
                    }

                    foreach ($result['subtitles'] ?? [] as $t) {
                        if (empty($t['file_path'])) continue;
                        Subtitle::create([
                            'episode_id' => $episode->id,
                            'language' => $t['language'] ?? 'en',
                            'label' => $t['label'] ?? strtoupper($t['language'] ?? 'en'),
                            'file_path' => $t['file_path'],
                            'is_default' => $t['is_default'] ?? false,
                        ]);
                    }
                });

                $success++;
                $bar->advance();
                usleep(500000);
            }

            $bar->finish();
            $this->newLine();
            $this->info("Done: {$success} imported, {$failed} failed");
        }

        return self::SUCCESS;
    }
}
