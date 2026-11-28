<?php

namespace App\Services;

use App\Support\EpisodeTitle;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnilistVideoSourceService
{
    private const LOOKUP_SECONDS = 40;

    private const DISCOVERY_TIMEOUT = 8;

    private const WATCH_TIMEOUT = 10;

    private string $anipubBaseUrl;

    private ?float $deadline = null;

    private ?string $lastError = null;

    private AnivexaVideoSourceService $anivexa;

    public function __construct()
    {
        $this->anipubBaseUrl = rtrim((string) config('services.anipub.base_url', 'https://anipub.xyz'), '/');
        $this->anivexa = app(AnivexaVideoSourceService::class);
    }

    public function fetchEpisodeSources(int $anilistId, int $episodeNumber, ?string $animeTitle = null): array
    {
        $cacheKey = 'sources:lookup:'.$anilistId.':'.$episodeNumber.':'.md5((string) $animeTitle);

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            $this->safeLog('warning', 'AnilistVideoSource cache read failed', ['error' => $e->getMessage()]);
        }

        $debug = [
            'anilist_id' => $anilistId,
            'episode' => $episodeNumber,
            'used_title' => $animeTitle,
        ];

        $result = $this->lookupSources($anilistId, $episodeNumber, $animeTitle);
        $result['debug'] = $debug;

        $seconds = str_starts_with((string) ($result['provider'] ?? ''), 'Anivexa')
            ? 60
            : (empty($result['sources']) ? 60 : 600);

        try {
            Cache::put($cacheKey, $result, now()->addSeconds($seconds));
        } catch (Throwable $e) {
            $this->safeLog('warning', 'AnilistVideoSource cache write failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    public function fetchEpisodeSourcesBatch(int $anilistId, array $episodeNumbers, ?string $animeTitle = null): array
    {
        $this->deadline = microtime(true) + 45;

        $numbers = array_values(array_unique(array_filter(array_map('intval', $episodeNumbers))));
        $results = [];

        if ($this->anivexa->isEnabled()) {
            try {
                $anivexaResults = $this->anivexa->fetchSourcesBatch($anilistId, $numbers);
                foreach ($anivexaResults as $number => $result) {
                    if (empty($result['sources'])) {
                        continue;
                    }

                    $results[(int) $number] = $result;

                    try {
                        $key = 'sources:lookup:'.$anilistId.':'.(int) $number.':'.md5((string) $animeTitle);
                        Cache::put($key, $result, now()->addSeconds(60));
                    } catch (Throwable $e) {
                        $this->safeLog('warning', 'AnilistVideoSource batch cache failed', ['error' => $e->getMessage()]);
                    }
                }
            } catch (Throwable $e) {
                $this->safeLog('warning', 'AnilistVideoSource Anivexa batch failed', ['error' => $e->getMessage()]);
            }
        }

        $pending = array_values(array_filter($numbers, fn ($number) => ! isset($results[$number])));
        if (empty($pending)) {
            return $results;
        }

        $anipubId = $this->resolveAnipubId($anilistId, $animeTitle);

        $anipubDetails = null;
        if ($anipubId > 0) {
            $timeout = $this->remainingTimeout(self::DISCOVERY_TIMEOUT);
            if ($timeout > 0) {
                $anipubDetails = $this->get("{$this->anipubBaseUrl}/v1/api/details/{$anipubId}", $timeout);
            }
        }

        foreach (array_chunk($pending, 8) as $chunk) {
            foreach ($chunk as $number) {
                $result = ['sources' => [], 'subtitles' => [], 'error' => 'No sources found'];

                if ($anipubDetails !== null) {
                    $fallback = $this->parseAnipubDetails($anipubDetails, $number);
                    if (! empty($fallback['sources'])) {
                        $fallback['provider'] = 'AniPub';
                        $result = $fallback;
                    } elseif (! empty($fallback['error'])) {
                        $result['error'] = $fallback['error'];
                    }
                }

                if (empty($result['sources'])) {
                    $result['error'] = $result['error'] ?? 'No sources found';
                }

                $results[$number] = $result;

                try {
                    $key = 'sources:lookup:'.$anilistId.':'.$number.':'.md5((string) $animeTitle);
                    Cache::put($key, $result, now()->addSeconds(empty($result['sources']) ? 60 : 600));
                } catch (Throwable $e) {
                    $this->safeLog('warning', 'AnilistVideoSource batch cache failed', ['error' => $e->getMessage()]);
                }
            }

            if ($this->remainingTimeout(1) <= 0) {
                break;
            }
        }

        return $results;
    }

    public function fetchEpisodeList(int $anilistId, ?string $animeTitle = null): array
    {
        try {
            $this->deadline = microtime(true) + self::LOOKUP_SECONDS;
            $this->lastError = null;

            $anivexaError = null;
            if ($this->anivexa->isEnabled()) {
                try {
                    $anivexaList = $this->anivexa->fetchEpisodeList($anilistId);
                    if (! empty($anivexaList['episodes'])) {
                        return $anivexaList;
                    }
                    $anivexaError = $anivexaList['error'] ?? null;
                } catch (Throwable $e) {
                    $anivexaError = $e->getMessage();
                }
            }

            $fallback = $this->fetchAniListEpisodes($anilistId, $animeTitle);
            if (! empty($fallback['episodes'])) {
                return $fallback;
            }

            $errors = [$fallback['error'] ?? 'No episode list providers available'];
            if (! empty($anivexaError)) {
                $errors[] = 'Anivexa: '.$anivexaError;
            }

            return ['error' => implode(' | ', $errors)];
        } catch (Throwable $e) {
            return ['error' => 'Episode list lookup failed: '.$e->getMessage()];
        }
    }

    private function fetchAniListEpisodes(int $anilistId, ?string $animeTitle): array
    {
        $url = rtrim((string) config('services.anilist.graphql_url', 'https://graphql.anilist.co'), '/');
        $timeout = $this->remainingTimeout(8);
        if ($timeout <= 0) {
            return ['error' => 'AniList fallback timed out'];
        }

        $query = 'query ($id: Int) { Media(id: $id, type: ANIME) { id episodes title { romaji english native } streamingEpisodes { title thumbnail url site } } }';

        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->connectTimeout(3)
                ->timeout($timeout)
                ->post($url, ['query' => $query, 'variables' => ['id' => $anilistId]]);
        } catch (Throwable $e) {
            return ['error' => 'AniList fallback failed: '.$e->getMessage()];
        }

        if (! $response instanceof Response) {
            return ['error' => 'AniList fallback failed'];
        }

        if (! $response->ok()) {
            return ['error' => 'AniList returned HTTP '.$response->status()];
        }

        $json = $response->json();
        $errors = Arr::get($json ?? [], 'errors', []);
        if (! empty($errors[0]['message'])) {
            return ['error' => 'AniList GraphQL: '.$errors[0]['message']];
        }

        $media = Arr::get($json ?? [], 'data.Media', []);
        if (! is_array($media) || empty($media)) {
            return ['error' => 'AniList media not found'];
        }

        $streamingEpisodes = Arr::get($media, 'streamingEpisodes', []);
        $episodes = [];

        if (is_array($streamingEpisodes)) {
            foreach ($streamingEpisodes as $index => $episode) {
                if (! is_array($episode)) {
                    continue;
                }
                $number = $index + 1;
                $episodes[] = [
                    'number' => $number,
                    'title' => EpisodeTitle::title(Arr::get($episode, 'title'), $number),
                    'image' => Arr::get($episode, 'thumbnail'),
                    'description' => null,
                    'releaseDate' => null,
                    'url' => Arr::get($episode, 'url'),
                ];
            }
        }

        $metadataCount = min(1000, max(0, (int) Arr::get($media, 'episodes', 0)));
        for ($number = count($episodes) + 1; $number <= $metadataCount; $number++) {
            $episodes[] = [
                'number' => $number,
                'title' => "Episode {$number}",
                'image' => null,
                'description' => null,
                'releaseDate' => null,
                'url' => null,
            ];
        }

        if (empty($episodes)) {
            return ['error' => 'AniList has no episode list for this anime'];
        }

        return [
            'episodes' => $episodes,
            'provider' => 'AniList',
            'totalEpisodes' => count($episodes),
            'title' => Arr::get($media, 'title.romaji') ?: Arr::get($media, 'title.english') ?: $animeTitle,
        ];
    }

    private function lookupSources(int $anilistId, int $episodeNumber, ?string $animeTitle): array
    {
        $this->deadline = microtime(true) + self::LOOKUP_SECONDS;

        $anivexaError = null;
        if ($this->anivexa->isEnabled()) {
            try {
                $anivexa = $this->anivexa->fetchSources($anilistId, $episodeNumber);
                if (! empty($anivexa['sources'])) {
                    $this->safeLog('info', 'AnilistVideoSource: Found sources via Anivexa', [
                        'anilist_id' => $anilistId, 'episode' => $episodeNumber,
                    ]);

                    return $anivexa;
                }
                $anivexaError = $anivexa['error'] ?? null;
            } catch (Throwable $e) {
                $anivexaError = $e->getMessage();
            }
        }

        $anipubId = $this->resolveAnipubId($anilistId, $animeTitle);

        $detailsResponse = null;
        if ($anipubId > 0) {
            $timeout = $this->remainingTimeout(self::WATCH_TIMEOUT);
            if ($timeout > 0) {
                $detailsResponse = $this->get("{$this->anipubBaseUrl}/v1/api/details/{$anipubId}", $timeout);
            }
        }

        if ($detailsResponse !== null) {
            $result = $this->parseAnipubDetails($detailsResponse, $episodeNumber);
            if (! empty($result['sources'])) {
                $result['provider'] = 'AniPub';
                $this->safeLog('info', 'AnilistVideoSource: Found sources via AniPub', [
                    'anilist_id' => $anilistId, 'episode' => $episodeNumber,
                ]);

                return $result;
            }
        }

        $error = "No sources found for AniList ID {$anilistId}, episode {$episodeNumber}";
        if (! empty($anivexaError)) {
            $error .= ' | Anivexa: '.$anivexaError;
        }

        return [
            'sources' => [],
            'subtitles' => [],
            'error' => $error,
        ];
    }

    private function resolveAnipubId(int $anilistId, ?string $animeTitle): int
    {
        if (! $animeTitle) {
            return 0;
        }

        $key = 'sources:anipub:'.$anilistId.':'.md5($animeTitle);
        $cached = (int) $this->cacheValue($key);
        if ($cached > 0) {
            return $cached;
        }

        $timeout = $this->remainingTimeout(self::DISCOVERY_TIMEOUT);
        if ($timeout <= 0) {
            return 0;
        }

        $findUrl = "{$this->anipubBaseUrl}/api/find/".urlencode($animeTitle);
        $searchUrl = "{$this->anipubBaseUrl}/api/search/".urlencode($animeTitle);

        try {
            $responses = Http::withHeaders(['Accept' => 'application/json'])
                ->pool(function ($pool) use ($findUrl, $searchUrl, $timeout) {
                    $pool->as('find')->connectTimeout(3)->timeout($timeout)->get($findUrl);
                    $pool->as('search')->connectTimeout(3)->timeout($timeout)->get($searchUrl);
                });
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return 0;
        }

        $id = $this->parseAnipubDiscovery(
            $responses['find'] ?? null,
            $responses['search'] ?? null,
            $animeTitle
        );

        if ($id > 0) {
            $this->putCache($key, $id, 86400);
        }

        return $id;
    }

    private function parseAnipubDiscovery(mixed $find, mixed $search, string $title): int
    {
        if ($this->isOk($find)) {
            $body = $find->json();
            if (($body['exist'] ?? false) && ! empty($body['id'])) {
                return (int) $body['id'];
            }
        }

        if (! $this->isOk($search)) {
            return 0;
        }

        $body = $search->json();
        if (empty($body) || (isset($body['found']) && $body['found'] === false)) {
            return 0;
        }

        if (isset($body['Id'])) {
            return (int) $body['Id'];
        }

        if (! is_array($body)) {
            return 0;
        }

        $titleLower = mb_strtolower($title);
        $best = 0;
        $bestScore = 0;

        foreach ($body as $result) {
            if (! is_array($result)) {
                continue;
            }
            $name = (string) ($result['Name'] ?? '');
            if ($name === '') {
                continue;
            }
            similar_text($titleLower, mb_strtolower($name), $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = (int) ($result['Id'] ?? 0);
            }
        }

        return $best;
    }

    private function parseAnipubDetails(mixed $response, int $episodeNumber): array
    {
        if (! $this->isOk($response)) {
            return ['sources' => [], 'subtitles' => [], 'error' => 'AniPub stream fetch failed'];
        }

        $local = Arr::get($response->json(), 'local', []);
        $episodeLink = null;

        if ($episodeNumber === 1) {
            $episodeLink = Arr::get($local, 'link', '');
        } elseif ($episodeNumber >= 2) {
            $episodes = Arr::get($local, 'ep', []);
            $index = $episodeNumber - 2;
            $episodeLink = isset($episodes[$index]) ? (string) Arr::get($episodes[$index], 'link', '') : null;
        }

        if (! $episodeLink) {
            return ['sources' => [], 'subtitles' => [], 'error' => "AniPub: episode {$episodeNumber} not found"];
        }

        return [
            'sources' => [[
                'label' => 'AniPub',
                'quality' => '720p',
                'url' => preg_replace('/^src=/', '', $episodeLink),
                'type' => 'embed',
                'language' => 'sub',
                'is_active' => true,
                'video_server_id' => null,
                'headers' => [],
                'provider' => 'AniPub',
                'metadata' => [
                    'provider' => 'AniPub',
                    'format' => 'embed',
                    'fetched_at' => time(),
                ],
            ]],
            'subtitles' => [],
            'headers' => [],
        ];
    }

    private function cacheValue(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable $e) {
            $this->safeLog('warning', 'AnilistVideoSource cache read failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function putCache(string $key, mixed $value, int $seconds): void
    {
        try {
            Cache::put($key, $value, $seconds);
        } catch (Throwable $e) {
            $this->safeLog('warning', 'AnilistVideoSource cache write failed', ['error' => $e->getMessage()]);
        }
    }

    private function get(string $url, int $timeout): ?Response
    {
        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->connectTimeout(3)
                ->timeout($timeout)
                ->get($url);

            return $response instanceof Response ? $response : null;
        } catch (Throwable $e) {
            $this->safeLog('warning', 'AnilistVideoSource request failed', [
                'url' => $url, 'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function responseError(mixed $response, string $fallback): string
    {
        if (! $response instanceof Response) {
            return $fallback;
        }

        $json = $response->json();
        $message = Arr::get($json ?? [], 'error')
            ?: Arr::get($json ?? [], 'message')
            ?: Arr::get($json ?? [], 'errors.0.message');

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    private function isOk(mixed $response): bool
    {
        return $response instanceof Response && $response->ok();
    }

    private function remainingTimeout(int $max): int
    {
        if ($this->deadline === null) {
            return $max;
        }

        return max(0, min($max, (int) ceil($this->deadline - microtime(true))));
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (Throwable $e) {
            return;
        }
    }
}
