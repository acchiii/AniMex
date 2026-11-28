<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\EpisodeSource;
use App\Models\Subtitle;
use Illuminate\Support\Facades\DB;

class EpisodeSourceStoreService
{
    public function store(Episode $episode, array $result, bool $replace = true): void
    {
        $sources = $this->normalizeSources($result['sources'] ?? []);
        $subtitles = $this->normalizeSubtitles($result['subtitles'] ?? []);

        DB::transaction(function () use ($episode, $sources, $subtitles, $replace): void {
            if ($replace) {
                EpisodeSource::where('episode_id', $episode->id)->delete();
                Subtitle::where('episode_id', $episode->id)->delete();
            }

            $sort = $replace ? 0 : (int) $episode->sources()->max('sort_order') + 1;
            foreach ($sources as $source) {
                EpisodeSource::create([
                    'episode_id' => $episode->id,
                    'video_server_id' => null,
                    'label' => $source['label'],
                    'quality' => $source['quality'],
                    'url' => $source['url'],
                    'headers' => $source['headers'],
                    'metadata' => $source['metadata'],
                    'provider' => $source['provider'],
                    'expires_at' => now()->addSeconds(60),
                    'is_active' => $source['is_active'],
                    'sort_order' => $sort++,
                    'type' => $source['type'],
                    'language' => $source['language'],
                ]);
            }

            if (! $replace && $episode->subtitles()->exists()) {
                return;
            }

            foreach ($subtitles as $subtitle) {
                Subtitle::create([
                    'episode_id' => $episode->id,
                    'language' => $subtitle['language'],
                    'label' => $subtitle['label'],
                    'file_path' => $subtitle['file_path'],
                    'is_default' => $subtitle['is_default'],
                ]);
            }
        });
    }

    private function normalizeSources(mixed $sources): array
    {
        if (! is_array($sources)) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            $url = (string) ($source['url'] ?? '');
            if ($url === '' || isset($seen[$url]) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            if (preg_match('/\.(replace|replaceAll|toString|split|join|concat)\(/', $url)) {
                continue;
            }

            $seen[$url] = true;
            $metadata = is_array($source['metadata'] ?? null) ? $source['metadata'] : null;
            $quality = (string) ($source['quality'] ?? '720p');
            $headers = $source['headers'] ?? null;

            $normalized[] = [
                'label' => (string) ($source['label'] ?? $quality),
                'quality' => in_array($quality, ['360p', '480p', '720p', '1080p', '4K'], true) ? $quality : '720p',
                'url' => $url,
                'headers' => is_array($headers) ? $headers : null,
                'metadata' => $metadata,
                'provider' => (string) ($source['provider'] ?? ($metadata['provider'] ?? '')),
                'expires_at' => now()->addSeconds(60),
                'is_active' => (bool) ($source['is_active'] ?? true),
                'type' => (string) ($source['type'] ?? 'hls'),
                'language' => (string) ($source['language'] ?? 'sub'),
            ];
        }

        return $normalized;
    }

    private function normalizeSubtitles(mixed $subtitles): array
    {
        if (! is_array($subtitles)) {
            return [];
        }

        $candidates = [];
        $seen = [];
        $position = 0;

        foreach ($subtitles as $subtitle) {
            if (! is_array($subtitle)) {
                continue;
            }

            $filePath = (string) ($subtitle['file_path'] ?? '');
            $language = $this->normalizeLanguage($subtitle['language'] ?? 'en');
            if ($filePath === '' || $language === null) {
                continue;
            }

            $key = $language.':'.$this->subtitleKey($filePath);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $label = (string) ($subtitle['label'] ?? strtoupper($language));
            $isDefault = (bool) ($subtitle['is_default'] ?? $subtitle['default'] ?? false);
            $format = strtolower((string) pathinfo((string) parse_url($filePath, PHP_URL_PATH), PATHINFO_EXTENSION));

            $score = $isDefault ? 1000 : 0;
            if (! $isDefault && in_array($format, ['vtt', 'srt'], true)) {
                $score += 50;
            }
            if (preg_match('/sign|song|ai|dubtitle/i', $label)) {
                $score -= 200;
            }

            $candidates[$language][] = [
                'language' => $language,
                'label' => $label,
                'file_path' => $filePath,
                'is_default' => $isDefault,
                'score' => $score,
                'position' => $position++,
            ];
        }

        $selected = [];
        foreach (['en', 'ja'] as $language) {
            $items = $candidates[$language] ?? [];
            if ($items === []) {
                continue;
            }

            usort($items, function (array $a, array $b): int {
                return [$b['score'], $a['position']] <=> [$a['score'], $b['position']];
            });

            $chosen = $items[0];
            $chosen['is_default'] = $language === 'en' || ($language === 'ja' && ! isset($candidates['en']));
            unset($chosen['score'], $chosen['position']);
            $selected[] = $chosen;
        }

        return $selected;
    }

    private function normalizeLanguage(mixed $language): ?string
    {
        $value = strtolower((string) $language);
        if (str_starts_with($value, 'ja') || str_starts_with($value, 'jp')) {
            return 'ja';
        }
        if (str_starts_with($value, 'en')) {
            return 'en';
        }

        return null;
    }

    private function subtitleKey(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return strtolower(basename($path));
    }
}
