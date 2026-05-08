<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnilistVideoSourceService
{
    private string $baseUrl;
    private string $defaultProvider;
    private array $fallbackProviders;
    private string $anipubBaseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('CONSUMET_BASE_URL', config('services.consumet.base_url', 'http://localhost:3000')), '/');
        $this->defaultProvider = (string) env('CONSUMET_PROVIDER', config('services.consumet.provider', 'gogoanime'));
        $this->fallbackProviders = config('services.consumet.fallback_providers', ['zoro', 'enime']);
        $this->anipubBaseUrl = rtrim((string) env('ANIPUB_BASE_URL', config('services.anipub.base_url', 'https://anipub.xyz')), '/');
    }

    public function fetchEpisodeSources(int $anilistId, int $episodeNumber, ?string $animeTitle = null): array
    {
        $providers = array_unique(array_merge(
            [$this->defaultProvider],
            $this->fallbackProviders
        ));

        foreach ($providers as $provider) {
            $result = $this->tryConsumetProvider($provider, $anilistId, $episodeNumber);
            if (empty($result['error']) && !empty($result['sources'])) {
                Log::info('AnilistVideoSource: Found sources via Consumet', [
                    'provider' => $provider,
                    'anilist_id' => $anilistId,
                    'episode' => $episodeNumber,
                ]);
                return $result;
            }
        }

        if ($animeTitle) {
            $result = $this->tryAnipub($animeTitle, $episodeNumber);
            if (empty($result['error']) && !empty($result['sources'])) {
                Log::info('AnilistVideoSource: Found sources via AniPub', [
                    'anilist_id' => $anilistId,
                    'episode' => $episodeNumber,
                ]);
                return $result;
            }
        }

        return [
            'sources' => [], 'subtitles' => [],
            'error' => "No sources found for AniList ID {$anilistId}, episode {$episodeNumber}",
        ];
    }

    private function tryConsumetProvider(string $provider, int $anilistId, int $episodeNumber): array
    {
        if ($this->baseUrl === '') {
            return ['sources' => [], 'subtitles' => [], 'error' => 'CONSUMET_BASE_URL not configured'];
        }

        try {
            $episodeId = $this->getEpisodeId($anilistId, $episodeNumber, $provider);
            if (!$episodeId) {
                return [
                    'sources' => [], 'subtitles' => [],
                    'error' => "Episode {$episodeNumber} not found via {$provider}",
                ];
            }

            return $this->getStreamingSources($episodeId, $provider);
        } catch (\Exception $e) {
            return ['sources' => [], 'subtitles' => [], 'error' => "{$provider}: " . $e->getMessage()];
        }
    }

    private function getEpisodeId(int $anilistId, int $episodeNumber, string $provider): ?string
    {
        $page = 1;
        $lastPage = 1;

        while ($page <= $lastPage) {
            $resp = Http::withHeaders(['Accept' => 'application/json'])
                ->timeout(30)
                ->get("{$this->baseUrl}/meta/anilist/info/{$anilistId}", [
                    'provider' => $provider,
                    'page' => $page,
                ]);

            if (!$resp->ok()) {
                return null;
            }

            $data = $resp->json();
            $episodes = Arr::get($data, 'episodes', []);
            $lastPage = (int) Arr::get($data, 'lastPage', 1);

            foreach ($episodes as $ep) {
                if ((int) Arr::get($ep, 'chapter', 0) === $episodeNumber) {
                    return (string) Arr::get($ep, 'id', '');
                }
            }

            $page++;
        }

        return null;
    }

    private function getStreamingSources(string $episodeId, string $provider): array
    {
        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(30)
            ->get("{$this->baseUrl}/meta/anilist/watch/{$episodeId}");

        if (!$resp->ok()) {
            return [
                'sources' => [], 'subtitles' => [],
                'error' => 'Failed to fetch streaming sources', 'status' => $resp->status(),
            ];
        }

        $payload = $resp->json();
        $headers = Arr::get($payload, 'headers', []);

        $sources = [];
        foreach (Arr::get($payload, 'sources', []) as $s) {
            $url = (string) Arr::get($s, 'url', '');
            if ($url === '') continue;

            $quality = (string) Arr::get($s, 'quality', '720');
            $quality = is_numeric($quality) ? $quality . 'p' : $quality;
            if (!in_array($quality, ['360p', '480p', '720p', '1080p', '4K'], true)) {
                $quality = '720p';
            }

            $sources[] = [
                'label' => $quality,
                'quality' => $quality,
                'url' => $url,
                'type' => (bool) Arr::get($s, 'isM3U8', false) ? 'hls' : 'mp4',
                'language' => 'sub',
                'is_active' => true,
                'video_server_id' => null,
                'headers' => $headers,
            ];
        }

        $subtitles = [];
        foreach (Arr::get($payload, 'subtitles', []) as $t) {
            $url = (string) Arr::get($t, 'url', '');
            if ($url === '') continue;

            $lang = (string) Arr::get($t, 'lang', 'en');

            $subtitles[] = [
                'language' => $lang,
                'label' => strtoupper($lang),
                'file_path' => $url,
                'is_default' => $lang === 'en',
            ];
        }

        return compact('sources', 'subtitles') + ['headers' => $headers];
    }

    private function findAnipubId(string $title): int
    {
        $findResp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(10)
            ->get("{$this->anipubBaseUrl}/api/find/" . urlencode($title));

        if ($findResp->ok()) {
            $found = $findResp->json();
            if (($found['exist'] ?? false) && !empty($found['id'])) {
                return (int) $found['id'];
            }
        }

        $searchResp = Http::withHeaders(['Accept' => 'application/json'])
            ->timeout(10)
            ->get("{$this->anipubBaseUrl}/api/search/" . urlencode($title));

        if (!$searchResp->ok()) return 0;

        $body = $searchResp->json();
        if (empty($body) || isset($body['found']) && $body['found'] === false) return 0;

        // Single object result (not wrapped in array)
        if (isset($body['Id'])) {
            return (int) $body['Id'];
        }

        // Array of results
        if (is_array($body)) {
            $titleLower = mb_strtolower($title);
            $best = 0;
            $bestScore = 0;

            foreach ($body as $r) {
                if (!is_array($r)) continue;
                $name = (string) ($r['Name'] ?? '');
                if ($name === '') continue;

                similar_text($titleLower, mb_strtolower($name), $score);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = (int) ($r['Id'] ?? 0);
                }
            }

            return $best;
        }

        return 0;
    }

    private function tryAnipub(string $title, int $episodeNumber): array
    {
        try {
            $anipubId = $this->findAnipubId($title);
            if ($anipubId <= 0) {
                return ['sources' => [], 'subtitles' => [], 'error' => 'AniPub: anime not found'];
            }

            $streamResp = Http::withHeaders(['Accept' => 'application/json'])
                ->timeout(15)
                ->get("{$this->anipubBaseUrl}/v1/api/details/{$anipubId}");

            if (!$streamResp->ok()) {
                return ['sources' => [], 'subtitles' => [], 'error' => 'AniPub stream fetch failed'];
            }

            $data = $streamResp->json();
            $local = Arr::get($data, 'local', []);

            $epLink = null;
            if ($episodeNumber === 1) {
                $epLink = Arr::get($local, 'link', '');
            } elseif ($episodeNumber >= 2) {
                $epArray = Arr::get($local, 'ep', []);
                $epIndex = $episodeNumber - 2;
                $epLink = isset($epArray[$epIndex]) ? (string) Arr::get($epArray[$epIndex], 'link', '') : null;
            }

            if (!$epLink) {
                return ['sources' => [], 'subtitles' => [], 'error' => "AniPub: episode {$episodeNumber} not found"];
            }

            $epLink = ltrim($epLink, 'src=');

            $sources[] = [
                'label' => 'AniPub',
                'quality' => '720p',
                'url' => $epLink,
                'type' => 'embed',
                'language' => 'sub',
                'is_active' => true,
                'video_server_id' => null,
                'headers' => [],
            ];

            return ['sources' => $sources, 'subtitles' => [], 'headers' => []];
        } catch (\Exception $e) {
            return ['sources' => [], 'subtitles' => [], 'error' => 'AniPub error: ' . $e->getMessage()];
        }
    }
}
