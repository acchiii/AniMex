<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnilistVideoSourceService
{
    private string $consumetBaseUrl;
    private string $anipubBaseUrl;
    private array $infoCache = [];

    public function __construct()
    {
        $this->consumetBaseUrl = rtrim((string) env('CONSUMET_BASE_URL', config('services.consumet.base_url', 'http://localhost:3000')), '/');
        $this->anipubBaseUrl = rtrim((string) env('ANIPUB_BASE_URL', config('services.anipub.base_url', 'https://anipub.xyz')), '/');
    }

    public function fetchEpisodeSources(int $anilistId, int $episodeNumber, ?string $animeTitle = null): array
    {
        $debug = [
            'anilist_id' => $anilistId,
            'episode' => $episodeNumber,
            'used_title' => $animeTitle,
        ];

        $result = $this->tryConsumet($anilistId, $episodeNumber, $animeTitle);
        if (empty($result['error']) && !empty($result['sources'])) {
            Log::info('AnilistVideoSource: Found sources via Consumet', $debug);
            return $result;
        }

        Log::warning('AnilistVideoSource: Consumet failed', array_merge($debug, [
            'error' => $result['error'] ?? null,
        ]));

        if ($animeTitle) {
            $result = $this->tryAnipub($animeTitle, $episodeNumber);
            if (empty($result['error']) && !empty($result['sources'])) {
                Log::info('AnilistVideoSource: Found sources via AniPub', $debug);
                return $result;
            }
        }

        return [
            'sources' => [], 'subtitles' => [],
            'error' => "No sources found for AniList ID {$anilistId}, episode {$episodeNumber}",
            'debug' => $debug,
        ];
    }

    private function fetchInfo(int $anilistId, ?string $animeTitle): array
    {
        if (isset($this->infoCache[$anilistId])) {
            return $this->infoCache[$anilistId];
        }

        $url = "{$this->consumetBaseUrl}/meta/anilist/info/{$anilistId}";
        if ($animeTitle) {
            $url .= '?title=' . urlencode($animeTitle);
        }

        $resp = Http::withHeaders(['Accept' => 'application/json'])
            ->connectTimeout(5)
            ->timeout(20)
            ->get($url);

        if (!$resp->ok()) {
            throw new \RuntimeException("Consumet info endpoint returned {$resp->status()}");
        }

        $data = $resp->json();
        $this->infoCache[$anilistId] = $data;
        return $data;
    }

    public function fetchEpisodeList(int $anilistId, ?string $animeTitle = null): array
    {
        try {
            $data = $this->fetchInfo($anilistId, $animeTitle);
            $episodes = Arr::get($data, 'episodes', []);

            return [
                'episodes' => $episodes,
                'provider' => $data['_provider'] ?? null,
                'totalEpisodes' => $data['totalEpisodes'] ?? count($episodes),
                'title' => $data['title'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Consumet server unreachable: ' . $e->getMessage()];
        }
    }

    private function tryConsumet(int $anilistId, int $episodeNumber, ?string $animeTitle): array
    {
        try {
            $info = $this->fetchInfo($anilistId, $animeTitle);
            $episodes = Arr::get($info, 'episodes', []);

            $episodeId = null;
            foreach ($episodes as $ep) {
                $num = (int) Arr::get($ep, 'number', 0);
                if ($num === $episodeNumber) {
                    $episodeId = Arr::get($ep, 'id');
                    break;
                }
            }

            if (!$episodeId) {
                return ['sources' => [], 'subtitles' => [], 'error' => "Episode {$episodeNumber} not found in provider's list"];
            }

            $provider = Arr::get($info, '_provider');
            $watchUrl = "{$this->consumetBaseUrl}/meta/anilist/watch/" . urlencode($episodeId);
            if ($provider) {
                $watchUrl .= '?provider=' . urlencode($provider);
            }

            $watchResp = Http::withHeaders(['Accept' => 'application/json'])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($watchUrl);

            if (!$watchResp->ok()) {
                return ['sources' => [], 'subtitles' => [], 'error' => "Watch endpoint returned {$watchResp->status()}"];
            }

            $watchData = $watchResp->json();
            $headers = Arr::get($watchData, 'headers', []);

            $sources = [];
            foreach (Arr::get($watchData, 'sources', []) as $s) {
                $url = (string) Arr::get($s, 'url', '');
                if ($url === '') continue;

                $quality = (string) Arr::get($s, 'quality', '720p');
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
            $allowedLangs = ['en', 'ja'];
            $langLabels = [
                'en' => 'English',
                'ja' => '日本語',
            ];
            foreach (Arr::get($watchData, 'subtitles', []) as $t) {
                $url = (string) Arr::get($t, 'url', '');
                if ($url === '') continue;

                $lang = (string) Arr::get($t, 'lang', 'en');

                if (!in_array($lang, $allowedLangs, true)) continue;

                $subtitles[] = [
                    'language' => $lang,
                    'label' => $langLabels[$lang] ?? strtoupper($lang),
                    'file_path' => $url,
                    'is_default' => $lang === 'en',
                ];
            }

            return compact('sources', 'subtitles') + ['headers' => $headers];
        } catch (\Exception $e) {
            return ['sources' => [], 'subtitles' => [], 'error' => 'Consumet: ' . $e->getMessage()];
        }
    }

    private function findAnipubId(string $title): int
    {
        $findResp = Http::withHeaders(['Accept' => 'application/json'])
            ->connectTimeout(3)
            ->timeout(10)
            ->get("{$this->anipubBaseUrl}/api/find/" . urlencode($title));

        if ($findResp->ok()) {
            $found = $findResp->json();
            if (($found['exist'] ?? false) && !empty($found['id'])) {
                return (int) $found['id'];
            }
        }

        $searchResp = Http::withHeaders(['Accept' => 'application/json'])
            ->connectTimeout(3)
            ->timeout(10)
            ->get("{$this->anipubBaseUrl}/api/search/" . urlencode($title));

        if (!$searchResp->ok()) return 0;

        $body = $searchResp->json();
        if (empty($body) || isset($body['found']) && $body['found'] === false) return 0;

        if (isset($body['Id'])) {
            return (int) $body['Id'];
        }

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
                ->connectTimeout(3)
                ->timeout(10)
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
