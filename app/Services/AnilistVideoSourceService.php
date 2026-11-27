<?php

namespace App\Services;

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

    private string $consumetBaseUrl;

    private string $anipubBaseUrl;

    private array $infoCache = [];

    private ?float $deadline = null;

    private ?string $lastError = null;

    private AnivexaVideoSourceService $anivexa;

    public function __construct()
    {
        $this->consumetBaseUrl = rtrim((string) config('services.consumet.base_url', 'https://animex-consumet.vercel.app'), '/');
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

        $discovery = $this->discover($anilistId, $animeTitle, true);
        $info = $discovery['info'];
        $anipubId = (int) $discovery['anipub_id'];

        $anipubDetails = null;
        if ($anipubId > 0) {
            $timeout = $this->remainingTimeout(self::DISCOVERY_TIMEOUT);
            if ($timeout > 0) {
                $anipubDetails = $this->get("{$this->anipubBaseUrl}/v1/api/details/{$anipubId}", $timeout);
            }
        }

        foreach (array_chunk($pending, 8) as $chunk) {
            $requests = [];
            foreach ($chunk as $number) {
                $watch = $this->buildWatchUrl($info, $number);
                if ($watch['url']) {
                    $requests[$number] = $watch['url'];
                }
            }

            $responses = [];
            if (! empty($requests)) {
                $timeout = max(1, $this->remainingTimeout(self::WATCH_TIMEOUT));
                try {
                    $responses = Http::withHeaders(['Accept' => 'application/json'])
                        ->pool(function ($pool) use ($requests, $timeout) {
                            foreach ($requests as $number => $url) {
                                $pool->as((string) $number)->connectTimeout(3)->timeout($timeout)->get($url);
                            }
                        });
                } catch (Throwable $e) {
                    $this->safeLog('warning', 'AnilistVideoSource batch request failed', ['error' => $e->getMessage()]);
                }
            }

            foreach ($chunk as $number) {
                $response = $responses[$number] ?? $responses[(string) $number] ?? null;
                $result = $response !== null
                    ? $this->parseConsumetWatch($response)
                    : ['sources' => [], 'subtitles' => [], 'error' => 'No watch response'];

                if (empty($result['sources']) && $anipubDetails !== null) {
                    $fallback = $this->parseAnipubDetails($anipubDetails, $number);
                    if (! empty($fallback['sources'])) {
                        $result = $fallback;
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
            $info = $this->discover($anilistId, $animeTitle, false)['info'];
            $episodes = Arr::get($info, 'episodes', []);

            if (empty($episodes)) {
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

                $errors = [$this->lastError ?: 'Consumet server unreachable'];
                if (! empty($anivexaError)) {
                    $errors[] = 'Anivexa: '.$anivexaError;
                }
                if (! empty($fallback['error'])) {
                    $errors[] = 'AniList: '.$fallback['error'];
                }

                return ['error' => implode(' | ', $errors)];
            }

            return [
                'episodes' => $episodes,
                'provider' => $info['_provider'] ?? null,
                'totalEpisodes' => $info['totalEpisodes'] ?? count($episodes),
                'title' => $info['title'] ?? null,
            ];
        } catch (Throwable $e) {
            return ['error' => 'Consumet server unreachable: '.$e->getMessage()];
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
                    'title' => Arr::get($episode, 'title') ?: "Episode {$number}",
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

        $discovery = $this->discover($anilistId, $animeTitle, true);
        $info = $discovery['info'];
        $anipubId = (int) $discovery['anipub_id'];

        $watch = $this->buildWatchUrl($info, $episodeNumber);
        $detailsUrl = $anipubId > 0 ? "{$this->anipubBaseUrl}/v1/api/details/{$anipubId}" : null;

        $watchResponse = null;
        $detailsResponse = null;

        if ($watch['url'] && $detailsUrl) {
            $timeout = $this->remainingTimeout(self::WATCH_TIMEOUT);
            if ($timeout > 0) {
                try {
                    $responses = Http::withHeaders(['Accept' => 'application/json'])
                        ->pool(function ($pool) use ($watch, $detailsUrl, $timeout) {
                            $pool->as('watch')->connectTimeout(3)->timeout($timeout)->get($watch['url']);
                            $pool->as('details')->connectTimeout(3)->timeout($timeout)->get($detailsUrl);
                        });
                    $watchResponse = $responses['watch'] ?? null;
                    $detailsResponse = $responses['details'] ?? null;
                } catch (Throwable $e) {
                    $this->safeLog('warning', 'AnilistVideoSource parallel lookup failed', ['error' => $e->getMessage()]);
                }
            }
        } elseif ($watch['url']) {
            $timeout = $this->remainingTimeout(self::WATCH_TIMEOUT);
            if ($timeout > 0) {
                $watchResponse = $this->get($watch['url'], $timeout);
            }
        } elseif ($detailsUrl) {
            $timeout = $this->remainingTimeout(self::WATCH_TIMEOUT);
            if ($timeout > 0) {
                $detailsResponse = $this->get($detailsUrl, $timeout);
            }
        }

        $consumetError = $watch['error'] ?? 'Consumet info unavailable';
        if ($watchResponse !== null) {
            $result = $this->parseConsumetWatch($watchResponse);
            if (! empty($result['sources'])) {
                $this->safeLog('info', 'AnilistVideoSource: Found sources via Consumet', [
                    'anilist_id' => $anilistId, 'episode' => $episodeNumber,
                ]);

                return $result;
            }
            $consumetError = $result['error'] ?? $consumetError;
        }

        $this->safeLog('warning', 'AnilistVideoSource: Consumet failed', [
            'anilist_id' => $anilistId, 'episode' => $episodeNumber, 'error' => $consumetError,
        ]);

        if ($detailsResponse !== null) {
            $result = $this->parseAnipubDetails($detailsResponse, $episodeNumber);
            if (! empty($result['sources'])) {
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

    private function discover(int $anilistId, ?string $animeTitle, bool $includeAnipub): array
    {
        $infoKey = 'sources:info:'.$anilistId.':'.md5((string) $animeTitle);
        $info = $this->cachedInfo($infoKey, $anilistId);

        $anipubKey = ($includeAnipub && $animeTitle) ? 'sources:anipub:'.md5($animeTitle) : null;
        $anipubId = $anipubKey ? (int) $this->cacheValue($anipubKey) : 0;

        if ($info !== null && (! $anipubKey || $anipubId > 0)) {
            return ['info' => $info, 'anipub_id' => $anipubId];
        }

        $timeout = $this->remainingTimeout(self::DISCOVERY_TIMEOUT);
        if ($timeout <= 0) {
            return ['info' => $info ?? [], 'anipub_id' => $anipubId];
        }

        $infoUrl = "{$this->consumetBaseUrl}/meta/anilist/info/{$anilistId}";
        if ($animeTitle) {
            $infoUrl .= '?title='.urlencode($animeTitle);
        }
        $findUrl = $anipubKey && $anipubId <= 0
            ? "{$this->anipubBaseUrl}/api/find/".urlencode($animeTitle)
            : null;
        $searchUrl = $anipubKey && $anipubId <= 0
            ? "{$this->anipubBaseUrl}/api/search/".urlencode($animeTitle)
            : null;

        try {
            $responses = Http::withHeaders(['Accept' => 'application/json'])
                ->pool(function ($pool) use ($info, $infoUrl, $findUrl, $searchUrl, $timeout) {
                    if ($info === null) {
                        $pool->as('info')->connectTimeout(3)->timeout($timeout)->get($infoUrl);
                    }
                    if ($findUrl) {
                        $pool->as('find')->connectTimeout(3)->timeout($timeout)->get($findUrl);
                    }
                    if ($searchUrl) {
                        $pool->as('search')->connectTimeout(3)->timeout($timeout)->get($searchUrl);
                    }
                });
        } catch (Throwable $e) {
            $this->safeLog('warning', 'AnilistVideoSource discovery failed', ['error' => $e->getMessage()]);

            return ['info' => $info ?? [], 'anipub_id' => $anipubId];
        }

        if ($info === null) {
            $infoResponse = $responses['info'] ?? null;
            if ($this->isOk($infoResponse)) {
                $data = $infoResponse->json();
                if (is_array($data)) {
                    $info = $data;
                    $this->infoCache[$anilistId] = $data;
                    $this->putCache($infoKey, $data, 3600);
                }
            } else {
                $this->lastError = $this->responseError($infoResponse, 'Consumet server unreachable');
            }
        }

        if ($anipubKey && $anipubId <= 0) {
            $anipubId = $this->parseAnipubDiscovery(
                $responses['find'] ?? null,
                $responses['search'] ?? null,
                (string) $animeTitle
            );
            if ($anipubId > 0) {
                $this->putCache($anipubKey, $anipubId, 86400);
            }
        }

        return ['info' => $info ?? [], 'anipub_id' => $anipubId];
    }

    private function buildWatchUrl(array $info, int $episodeNumber): array
    {
        $episodeId = null;
        foreach (Arr::get($info, 'episodes', []) as $episode) {
            if ((int) Arr::get($episode, 'number', 0) === $episodeNumber) {
                $episodeId = Arr::get($episode, 'id');
                break;
            }
        }

        if (! $episodeId) {
            return ['url' => null, 'error' => "Episode {$episodeNumber} not found in provider's list"];
        }

        $provider = Arr::get($info, '_provider');
        $url = "{$this->consumetBaseUrl}/meta/anilist/watch/".urlencode((string) $episodeId);
        if ($provider) {
            $url .= '?provider='.urlencode((string) $provider);
        }

        return ['url' => $url, 'error' => null];
    }

    private function parseConsumetWatch(mixed $response): array
    {
        if (! $this->isOk($response)) {
            return ['sources' => [], 'subtitles' => [], 'error' => 'Watch endpoint unavailable'];
        }

        $data = $response->json();
        $headers = Arr::get($data, 'headers', []);

        $sources = [];
        foreach (Arr::get($data, 'sources', []) as $source) {
            $url = (string) Arr::get($source, 'url', '');
            if ($url === '') {
                continue;
            }

            $quality = (string) Arr::get($source, 'quality', '720p');
            $quality = is_numeric($quality) ? $quality.'p' : $quality;
            if (! in_array($quality, ['360p', '480p', '720p', '1080p', '4K'], true)) {
                $quality = '720p';
            }

            $sources[] = [
                'label' => $quality,
                'quality' => $quality,
                'url' => $url,
                'type' => (bool) Arr::get($source, 'isM3U8', false) ? 'hls' : 'mp4',
                'language' => 'sub',
                'is_active' => true,
                'video_server_id' => null,
                'headers' => $headers,
            ];
        }

        $subtitles = [];
        $langLabels = ['en' => 'English', 'ja' => '日本語'];
        foreach (Arr::get($data, 'subtitles', []) as $subtitle) {
            $url = (string) Arr::get($subtitle, 'url', '');
            $lang = (string) Arr::get($subtitle, 'lang', 'en');
            if ($url === '' || ! in_array($lang, ['en', 'ja'], true)) {
                continue;
            }

            $subtitles[] = [
                'language' => $lang,
                'label' => $langLabels[$lang] ?? strtoupper($lang),
                'file_path' => $url,
                'is_default' => $lang === 'en',
            ];
        }

        return ['sources' => $sources, 'subtitles' => $subtitles, 'headers' => $headers];
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
            ]],
            'subtitles' => [],
            'headers' => [],
        ];
    }

    private function cachedInfo(string $key, int $anilistId): ?array
    {
        if (isset($this->infoCache[$anilistId])) {
            return $this->infoCache[$anilistId];
        }

        $cached = $this->cacheValue($key);
        if (is_array($cached)) {
            $this->infoCache[$anilistId] = $cached;

            return $cached;
        }

        return null;
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
