<?php

namespace App\Services;

use App\Support\EpisodeTitle;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnivexaVideoSourceService
{
    private const REQUEST_TIMEOUT = 12;

    private const EPISODE_TIMEOUT = 30;

    private const EPISODE_CACHE_SECONDS = 600;

    private const WATCH_CACHE_SECONDS = 60;

    private const PROVIDER_HEALTH_SECONDS = 900;

    private const DEFAULT_PROVIDERS = [
        'anihq', 'reanime', 'anikoto', 'animegg', 'animenosub', 'anizone',
        'aniwaves', 'pahenime', 'kaa', 'animedunya', 'animeonsen', 'anipm',
    ];

    private string $baseUrl;

    private array $providers;

    private ?string $lastError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.anivexa.base_url', ''), '/');

        $providers = config('services.anivexa.providers', []);
        if (! is_array($providers)) {
            $providers = preg_split('/[,|\s]+/', (string) $providers) ?: [];
        }

        $providers = array_values(array_filter(array_map(
            fn ($provider) => strtolower(trim((string) $provider)),
            $providers
        )));

        $this->providers = $providers ?: self::DEFAULT_PROVIDERS;
    }

    public function isEnabled(): bool
    {
        return $this->baseUrl !== '';
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function fetchEpisodeList(int $anilistId): array
    {
        $this->lastError = null;

        if (! $this->isEnabled()) {
            return ['error' => 'Anivexa base URL is not configured'];
        }

        $index = $this->episodeIndex($anilistId);
        if (! empty($index['error'])) {
            return ['error' => $index['error']];
        }

        $expected = (int) Arr::get($index, 'mappings.episodes', 0);
        $best = $this->bestEpisodeProvider($index['providers'], $expected);

        if ($best === null || empty($best['episodes'])) {
            return ['error' => 'Anivexa returned no episode lists'];
        }

        $seen = [];
        $episodes = [];
        foreach ($best['episodes'] as $episode) {
            $number = (int) ($episode['number'] ?? 0);
            if ($number <= 0 || isset($seen[$number])) {
                continue;
            }
            if (($episode['audio'] ?? 'sub') !== 'sub') {
                continue;
            }
            $seen[$number] = true;

            $episodes[] = [
                'number' => $number,
                'title' => EpisodeTitle::title($episode['title'] ?? null, $number),
                'image' => $episode['image'] ?? null,
                'description' => $episode['description'] ?? null,
                'releaseDate' => $episode['releaseDate'] ?? null,
            ];
        }

        if (empty($episodes)) {
            return ['error' => 'Anivexa returned no usable episodes'];
        }

        return [
            'episodes' => $episodes,
            'provider' => 'Anivexa/'.$best['provider'],
            'totalEpisodes' => count($episodes),
            'title' => Arr::get($index, 'mappings.title'),
        ];
    }

    public function fetchSources(int $anilistId, int $episodeNumber): array
    {
        $this->lastError = null;

        if (! $this->isEnabled()) {
            return ['sources' => [], 'error' => 'Anivexa base URL is not configured'];
        }

        $candidates = $this->episodeCandidates($anilistId)[$episodeNumber] ?? [];
        if (empty($candidates)) {
            return ['sources' => [], 'error' => "Anivexa has no episode {$episodeNumber} for AniList ID {$anilistId}"];
        }

        $errors = [];
        foreach ($candidates as $candidate) {
            $result = $this->fetchWatch((string) $candidate['path'], (string) $candidate['provider']);
            if (! empty($result['sources'])) {
                return array_merge($result, ['provider' => 'Anivexa/'.$candidate['provider']]);
            }

            $errors[] = $result['error'] ?? 'No sources';
        }

        return [
            'sources' => [],
            'subtitles' => [],
            'error' => 'Anivexa: no playable sources'.(! empty($errors) ? ' ('.implode(' | ', $errors).')' : ''),
        ];
    }

    public function fetchSourcesBatch(int $anilistId, array $episodeNumbers): array
    {
        $this->lastError = null;

        $numbers = array_values(array_unique(array_filter(array_map('intval', $episodeNumbers))));
        if (empty($numbers)) {
            return [];
        }

        if (! $this->isEnabled()) {
            $result = [];
            foreach ($numbers as $number) {
                $result[$number] = ['sources' => [], 'error' => 'Anivexa base URL is not configured'];
            }

            return $result;
        }

        $candidateMap = $this->episodeCandidates($anilistId);
        $results = [];

        foreach (array_chunk($numbers, 8) as $chunk) {
            $requests = [];
            $candidateLists = [];

            foreach ($chunk as $number) {
                $candidateLists[$number] = array_slice($candidateMap[$number] ?? [], 0, 3);
                foreach ($candidateLists[$number] as $index => $candidate) {
                    $key = $number.':'.$index;
                    $requests[$key] = [
                        'url' => $this->watchUrl((string) $candidate['path']),
                        'provider' => (string) $candidate['provider'],
                    ];
                }
            }

            $responses = [];
            if (! empty($requests)) {
                try {
                    $responses = Http::withHeaders(['Accept' => 'application/json'])
                        ->pool(function ($pool) use ($requests) {
                            foreach ($requests as $key => $request) {
                                $pool->as($key)->connectTimeout(3)->timeout(self::REQUEST_TIMEOUT)->get($request['url']);
                            }
                        });
                } catch (Throwable $e) {
                    $this->safeLog('warning', 'Anivexa batch request failed', ['error' => $e->getMessage()]);
                }
            }

            foreach ($chunk as $number) {
                $candidates = $candidateLists[$number] ?? [];
                if (empty($candidates)) {
                    $results[$number] = ['sources' => [], 'error' => "Anivexa has no episode {$number}"];

                    continue;
                }

                $errors = [];
                foreach ($candidates as $index => $candidate) {
                    $key = $number.':'.$index;
                    $response = $responses[$key] ?? null;
                    $result = $this->parseWatchResponse($response, (string) $candidate['provider']);
                    if (! empty($result['sources'])) {
                        $this->markProviderSuccess((string) $candidate['provider']);
                        $results[$number] = array_merge($result, ['provider' => 'Anivexa/'.$candidate['provider']]);

                        continue 2;
                    }

                    $this->markProviderFailure((string) $candidate['provider']);
                    $errors[] = $result['error'] ?? 'No sources';
                }

                $results[$number] = [
                    'sources' => [],
                    'subtitles' => [],
                    'error' => 'Anivexa: no playable sources'.(! empty($errors) ? ' ('.implode(' | ', $errors).')' : ''),
                ];
            }
        }

        return $results;
    }

    private function bestEpisodeProvider(array $providers, int $expected): ?array
    {
        $best = null;
        $bestScore = -1;

        foreach ($providers as $provider) {
            $score = $this->providerScore($provider, $expected);
            if ($score > $bestScore) {
                $best = $provider;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function providerScore(array $provider, int $expected): int
    {
        $subNumbers = [];
        $hasDub = false;

        foreach ($provider['episodes'] ?? [] as $episode) {
            if (($episode['audio'] ?? 'sub') === 'dub') {
                $hasDub = true;

                continue;
            }
            $number = (int) ($episode['number'] ?? 0);
            if ($number > 0) {
                $subNumbers[$number] = $number;
            }
        }

        $numbers = array_values($subNumbers);
        sort($numbers, SORT_NUMERIC);

        $count = count($numbers);
        $contiguous = 0;
        $next = 1;
        foreach ($numbers as $number) {
            if ($number === $next) {
                $contiguous++;
                $next++;
            }
        }

        $coverage = $expected > 0 ? min($count, $expected) / $expected : 0;
        $match = (float) Arr::get($provider, 'meta.matchScore', 0);

        return ($contiguous * 1000)
            + ($count * 100)
            + (int) ($coverage * 500)
            + (int) ($match * 1000)
            + ($hasDub ? 50 : 0);
    }

    private function episodeIndex(int $anilistId): array
    {
        $providers = $this->activeProviders();
        $cacheKey = 'anivexa:episodes:'.md5($this->baseUrl.'|'.implode(',', $providers).'|'.$anilistId);
        $cached = $this->cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $chunks = array_values(array_chunk($providers, 3));
        $responses = [];

        try {
            $responses = Http::withHeaders(['Accept' => 'application/json'])
                ->pool(function ($pool) use ($chunks, $anilistId) {
                    foreach ($chunks as $index => $group) {
                        $url = $this->baseUrl.'/episodes/'.implode('/', $group).'/'.$anilistId.'?map=false';
                        $pool->as((string) $index)->connectTimeout(3)->timeout(self::EPISODE_TIMEOUT)->get($url);
                    }
                });
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            foreach ($providers as $provider) {
                $this->markProviderFailure($provider);
            }
        }

        $result = ['mappings' => [], 'providers' => []];
        $errors = [];

        foreach ($chunks as $index => $group) {
            $response = $responses[(string) $index] ?? null;
            if (! $this->isOk($response)) {
                $errors[] = $this->responseError($response, 'Anivexa episode request failed');
                foreach ($group as $provider) {
                    $this->markProviderFailure($provider);
                }

                continue;
            }

            $data = $response->json();
            if (! is_array($data)) {
                $errors[] = 'Anivexa returned an invalid episode response';
                foreach ($group as $provider) {
                    $this->markProviderFailure($provider);
                }

                continue;
            }

            $mappings = Arr::get($data, 'mappings', []);
            if (empty($result['mappings']) && is_array($mappings)) {
                $result['mappings'] = $mappings;
            }

            foreach ($group as $provider) {
                $block = Arr::get($data, $provider, []);
                if (! is_array($block) || isset($block['error'])) {
                    $errors[] = (string) (is_array($block) ? Arr::get($block, 'error') : null) ?: "{$provider}: unavailable";
                    $this->markProviderFailure($provider);

                    continue;
                }

                $lists = Arr::get($block, 'episodes', []);
                if (! is_array($lists)) {
                    $this->markProviderFailure($provider);

                    continue;
                }

                $episodes = [];
                $seen = [];

                foreach (['sub', 'dub'] as $audio) {
                    $list = Arr::get($lists, $audio, []);
                    if (! is_array($list)) {
                        continue;
                    }

                    foreach ($list as $episode) {
                        if (! is_array($episode)) {
                            continue;
                        }

                        $number = $this->episodeNumber($episode['number'] ?? null);
                        if ($number <= 0 || isset($seen[$number])) {
                            continue;
                        }
                        $seen[$number] = true;

                        $path = (string) ($episode['id'] ?? "watch/{$provider}/{$anilistId}/{$audio}/{$provider}-{$number}");
                        $episodes[] = [
                            'number' => $number,
                            'audio' => $audio,
                            'path' => ltrim($path, '/'),
                            'title' => $episode['title'] ?? null,
                            'image' => $episode['image'] ?? null,
                            'description' => $episode['description'] ?? null,
                            'releaseDate' => $episode['airDate'] ?? null,
                        ];
                    }
                }

                if (empty($episodes)) {
                    $this->markProviderFailure($provider);

                    continue;
                }

                $this->markProviderSuccess($provider);
                $result['providers'][] = [
                    'provider' => $provider,
                    'meta' => Arr::get($block, 'meta', []),
                    'episodes' => $episodes,
                ];
            }
        }

        if (empty($result['providers'])) {
            $this->lastError = (string) (Arr::get($result, 'mappings.error') ?: implode(' | ', $errors) ?: 'Anivexa returned no episode lists');

            return ['error' => $this->lastError];
        }

        $this->cachePut($cacheKey, $result, self::EPISODE_CACHE_SECONDS);

        return $result;
    }

    private function episodeCandidates(int $anilistId): array
    {
        $index = $this->episodeIndex($anilistId);
        if (! empty($index['error'])) {
            return [];
        }

        $providers = $index['providers'];

        $candidates = [];
        foreach ($providers as $provider) {
            foreach ($provider['episodes'] as $episode) {
                $number = (int) ($episode['number'] ?? 0);
                if ($number <= 0) {
                    continue;
                }

                if (! isset($candidates[$number])) {
                    $candidates[$number] = [];
                }

                $paths = array_column($candidates[$number], 'path');
                if (in_array($episode['path'], $paths, true)) {
                    continue;
                }

                if (count($candidates[$number]) >= 3) {
                    continue;
                }

                $candidates[$number][] = [
                    'provider' => $provider['provider'],
                    'path' => $episode['path'],
                    'audio' => $episode['audio'],
                ];
            }
        }

        return $candidates;
    }

    private function fetchWatch(string $path, string $provider): array
    {
        $cacheKey = 'anivexa:watch:'.md5($this->baseUrl.'|'.$path);
        $cached = $this->cacheGet($cacheKey);
        if (is_array($cached)) {
            if (empty($cached['sources'])) {
                $this->markProviderFailure($provider);
            } else {
                $this->markProviderSuccess($provider);
            }

            return $cached;
        }

        $response = $this->get($this->watchUrl($path));
        $result = $this->parseWatchResponse($response, $provider);
        $seconds = empty($result['sources']) ? 15 : self::WATCH_CACHE_SECONDS;
        $this->cachePut($cacheKey, $result, $seconds);

        if (empty($result['sources'])) {
            $this->markProviderFailure($provider);
        } else {
            $this->markProviderSuccess($provider);
        }

        return $result;
    }

    private function parseWatchResponse(mixed $response, string $provider): array
    {
        if (! $this->isOk($response)) {
            return [
                'sources' => [],
                'subtitles' => [],
                'error' => $this->responseError($response, 'Anivexa watch request failed'),
            ];
        }

        $data = $response->json();
        if (! is_array($data)) {
            return ['sources' => [], 'subtitles' => [], 'error' => 'Anivexa returned an invalid watch response'];
        }

        return $this->normalizeWatch($data, $provider);
    }

    private function normalizeWatch(array $data, string $provider): array
    {
        $headers = Arr::get($data, 'headers', []);
        if (! is_array($headers)) {
            $headers = [];
        }

        $streams = Arr::get($data, 'streams', []);
        if (! is_array($streams) || empty($streams)) {
            $streams = Arr::get($data, 'sources', []);
        }
        if (! is_array($streams)) {
            $streams = [];
        }

        $rootUrl = (string) (Arr::get($data, 'proxiedUrl') ?: Arr::get($data, 'stream_url') ?: '');
        $rootExists = false;
        if ($rootUrl !== '') {
            foreach ($streams as $stream) {
                if (! is_array($stream)) {
                    continue;
                }

                $streamUrl = (string) (Arr::get($stream, 'proxiedUrl') ?: Arr::get($stream, 'extractedUrl') ?: Arr::get($stream, 'url') ?: '');
                if ($streamUrl === $rootUrl) {
                    $rootExists = true;

                    break;
                }
            }
        }

        if ($rootUrl !== '' && ! $rootExists) {
            array_unshift($streams, [
                'url' => $rootUrl,
                'type' => 'hls',
                'server' => Arr::get($data, 'server') ?: $provider,
                'headers' => $headers,
                'subtitles' => Arr::get($data, 'subtitles', []),
            ]);
        }

        $sources = [];
        $seenSources = [];
        $subtitles = [];
        $seenSubtitles = [];

        foreach ($streams as $stream) {
            if (! is_array($stream)) {
                continue;
            }

            if (array_key_exists('isActive', $stream) && ! $stream['isActive']) {
                continue;
            }

            $rawType = strtolower((string) (Arr::get($stream, 'extractedType') ?: Arr::get($stream, 'type') ?: ''));
            $url = (string) (Arr::get($stream, 'proxiedUrl') ?: Arr::get($stream, 'extractedUrl') ?: Arr::get($stream, 'url') ?: '');

            if ($rawType === 'dash' || preg_match('/\.mpd(?:[?#]|$)/i', $url)) {
                $embedUrl = (string) (Arr::get($stream, 'embed') ?: Arr::get($stream, 'embedUrl') ?: '');
                if ($embedUrl === '') {
                    continue;
                }
                $url = $embedUrl;
                $rawType = 'embed';
            }

            $isHls = $rawType === 'hls' || preg_match('/\.m3u8(?:[?#]|$)/i', $url) || str_contains($url, 'master.txt');
            $isMp4 = $rawType === 'mp4' || preg_match('/\.mp4(?:[?#]|$)/i', $url);
            $isEmbed = in_array($rawType, ['embed', 'iframe', 'web'], true)
                || str_starts_with($rawType, 'iframe')
                || (! $isHls && ! $isMp4 && str_starts_with($url, 'http'));

            if ($url === '' || (! $isHls && ! $isMp4 && ! $isEmbed)) {
                continue;
            }

            $type = $isEmbed ? 'embed' : ($isHls ? 'hls' : 'mp4');
            if (isset($seenSources[$url])) {
                continue;
            }
            $seenSources[$url] = true;

            $streamHeaders = $headers;
            $extraHeaders = Arr::get($stream, 'headers', []);
            if (is_array($extraHeaders)) {
                $streamHeaders = array_merge($streamHeaders, $extraHeaders);
            }

            $providerReferers = [
                'reanime' => 'https://flixcloud.cc/',
                'pahenime' => 'https://pahe.in/',
                'anikoto' => 'https://anikoto.to/',
            ];
            $referer = Arr::get($stream, 'referer')
                ?: Arr::get($data, 'referer')
                ?: ($providerReferers[$provider] ?? null);
            if ($referer && ! isset($streamHeaders['Referer']) && ! isset($streamHeaders['referer'])) {
                $streamHeaders['Referer'] = (string) $referer;
            }

            $quality = $this->quality((string) (Arr::get($stream, 'quality') ?: ''));
            $label = (string) (Arr::get($stream, 'server') ?: Arr::get($stream, 'name') ?: $provider);
            $playlistKey = Arr::get($stream, 'playlist_key')
                ?: Arr::get($stream, 'playlistKey')
                ?: Arr::get($stream, 'key')
                ?: Arr::get($data, 'playlist_key')
                ?: Arr::get($data, 'playlistKey')
                ?: Arr::get($data, 'key');
            $wrappedFormat = Arr::get($stream, 'wrappedFormat') ?: Arr::get($data, 'wrappedFormat');
            $wrapperOffsets = Arr::get($stream, 'wrapperOffsets') ?: Arr::get($data, 'wrapperOffsets');

            $sources[] = [
                'label' => $label !== '' ? $label : $provider,
                'quality' => $quality,
                'url' => $url,
                'type' => $type,
                'language' => 'sub',
                'is_active' => true,
                'video_server_id' => null,
                'headers' => $streamHeaders,
                'provider' => $provider,
                'metadata' => array_filter([
                    'provider' => $provider,
                    'server' => (string) (Arr::get($stream, 'server') ?: Arr::get($data, 'server') ?: ''),
                    'referer' => (string) ($referer ?: ''),
                    'format' => $type,
                    'playlist_key' => $playlistKey,
                    'wrapped_format' => $wrappedFormat,
                    'wrapper_offsets' => is_array($wrapperOffsets) ? $wrapperOffsets : null,
                    'fetched_at' => time(),
                ], fn ($value) => $value !== null && $value !== ''),
            ];

            foreach ((array) Arr::get($stream, 'subtitles', []) as $subtitle) {
                $this->addSubtitle($subtitles, $seenSubtitles, $subtitle);
            }
        }

        foreach ((array) Arr::get($data, 'subtitles', []) as $subtitle) {
            $this->addSubtitle($subtitles, $seenSubtitles, $subtitle);
        }

        return ['sources' => $sources, 'subtitles' => $subtitles];
    }

    private function addSubtitle(array &$subtitles, array &$seen, mixed $subtitle): void
    {
        if (! is_array($subtitle)) {
            return;
        }

        $url = (string) (Arr::get($subtitle, 'url') ?: Arr::get($subtitle, 'file') ?: '');
        if ($url === '' || isset($seen[$url])) {
            return;
        }

        $lang = strtolower((string) (Arr::get($subtitle, 'srclang') ?: Arr::get($subtitle, 'lang') ?: 'en'));
        if (str_starts_with($lang, 'ja') || str_starts_with($lang, 'jp')) {
            $language = 'ja';
            $label = '日本語';
        } elseif (str_starts_with($lang, 'en')) {
            $language = 'en';
            $label = 'English';
        } else {
            return;
        }

        $seen[$url] = true;
        $subtitles[] = [
            'language' => $language,
            'label' => (string) (Arr::get($subtitle, 'label') ?: $label),
            'file_path' => $url,
            'is_default' => (bool) (Arr::get($subtitle, 'default') ?? ($language === 'en')),
        ];
    }

    private function quality(string $quality): string
    {
        $quality = strtolower(trim($quality));
        if (is_numeric($quality)) {
            $quality .= 'p';
        }

        if ($quality === '4k') {
            return '4K';
        }

        return in_array($quality, ['360p', '480p', '720p', '1080p'], true) ? $quality : '720p';
    }

    private function episodeNumber(mixed $value): int
    {
        if (! is_numeric($value)) {
            return 0;
        }

        $number = (float) $value;
        if (abs($number - round($number)) > 0.001) {
            return 0;
        }

        return max(0, (int) round($number));
    }

    private function watchUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->baseUrl.'/'.ltrim($path, '/');
    }

    private function get(string $url, ?int $timeout = null): ?Response
    {
        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->connectTimeout(3)
                ->timeout($timeout ?? self::REQUEST_TIMEOUT)
                ->get($url);

            return $response instanceof Response ? $response : null;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->safeLog('warning', 'Anivexa request failed', ['url' => $url, 'error' => $e->getMessage()]);

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

        return is_string($message) && $message !== '' ? $message : $fallback.' (HTTP '.$response->status().')';
    }

    private function isOk(mixed $response): bool
    {
        return $response instanceof Response && $response->ok();
    }

    private function activeProviders(): array
    {
        $active = [];

        foreach ($this->providers as $provider) {
            $health = $this->cacheGet('anivexa:health:'.$provider);
            $failures = is_array($health) ? (int) ($health['failures'] ?? 0) : 0;
            $until = is_array($health) ? (int) ($health['until'] ?? 0) : 0;

            if ($failures >= 2 && $until > time()) {
                continue;
            }

            $active[] = $provider;
        }

        return $active ?: $this->providers;
    }

    private function markProviderFailure(string $provider): void
    {
        $key = 'anivexa:health:'.$provider;
        $health = $this->cacheGet($key);
        $failures = is_array($health) ? (int) ($health['failures'] ?? 0) : 0;
        $failures++;

        $this->cachePut($key, [
            'failures' => min($failures, 3),
            'until' => $failures >= 2 ? time() + self::PROVIDER_HEALTH_SECONDS : 0,
        ], self::PROVIDER_HEALTH_SECONDS);
    }

    private function markProviderSuccess(string $provider): void
    {
        $this->cachePut('anivexa:health:'.$provider, [
            'failures' => 0,
            'until' => 0,
        ], self::PROVIDER_HEALTH_SECONDS);
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (Throwable $e) {
            $this->safeLog('warning', 'Anivexa cache read failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function cachePut(string $key, mixed $value, int $seconds): void
    {
        try {
            Cache::put($key, $value, $seconds);
        } catch (Throwable $e) {
            $this->safeLog('warning', 'Anivexa cache write failed', ['error' => $e->getMessage()]);
        }
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (Throwable) {
            return;
        }
    }
}
