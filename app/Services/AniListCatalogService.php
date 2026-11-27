<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AniListCatalogService
{
    private string $graphqlUrl;

    private ?string $lastError = null;

    public function __construct()
    {
        $this->graphqlUrl = (string) config('services.anilist.graphql_url', 'https://graphql.anilist.co');
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function search(string $query, int $page = 1, int $perPage = 20): array
    {
        $graphql = <<<'GQL'
            query ($search: String, $page: Int, $perPage: Int) {
                Page(page: $page, perPage: $perPage) {
                    media(search: $search, type: ANIME, sort: POPULARITY_DESC) {
                        id
                        idMal
                        title { romaji english native }
                        coverImage { extraLarge large }
                        format
                        episodes
                        averageScore
                        status
                    }
                }
            }
            GQL;

        $data = $this->graphql($graphql, [
            'search' => $query,
            'page' => max(1, $page),
            'perPage' => max(1, min(50, $perPage)),
        ], 300);

        $media = $data['Page']['media'] ?? [];
        $parsed = [];

        foreach (is_array($media) ? $media : [] as $item) {
            $malId = (int) ($item['idMal'] ?? 0);
            if ($malId <= 0) {
                continue;
            }

            $title = $item['title']['romaji'] ?? $item['title']['english'] ?? 'Unknown';
            $score = $item['averageScore'] ?? null;

            $parsed[] = [
                'mal_id' => $malId,
                'anilist_id' => (int) ($item['id'] ?? 0),
                'title' => $title,
                'title_english' => $item['title']['english'] ?? null,
                'image' => $item['coverImage']['extraLarge'] ?? $item['coverImage']['large'] ?? null,
                'type' => $this->mapType($item['format'] ?? ''),
                'episodes' => $item['episodes'] ?? '?',
                'score' => $score !== null ? round(((int) $score) / 10, 1) : 'N/A',
                'status' => $this->mapStatus($item['status'] ?? ''),
            ];
        }

        return $parsed;
    }

    public function getByMalId(int $malId): ?array
    {
        $graphql = <<<'GQL'
            query ($idMal: Int) {
                Media(idMal: $idMal, type: ANIME) {
                    id
                    idMal
                    title { romaji english native }
                    description(asHtml: false)
                    coverImage { extraLarge large }
                    bannerImage
                    format
                    status
                    season
                    seasonYear
                    startDate { year month day }
                    endDate { year month day }
                    episodes
                    duration
                    rating
                    source
                    averageScore
                    popularity
                    favourites
                    genres
                    studios(isMain: true) { edges { isMain node { name } } }
                }
            }
            GQL;

        $data = $this->graphql($graphql, ['idMal' => $malId], 3600);

        return $data['Media'] ?? null;
    }

    public function parseAnimeData(array $data, ?int $fallbackMalId = null): array
    {
        $title = $data['title']['romaji'] ?? $data['title']['english'] ?? $data['title']['native'] ?? 'Unknown';
        $startDate = $this->dateString($data['startDate'] ?? null);
        $endDate = $this->dateString($data['endDate'] ?? null);
        $score = $data['averageScore'] ?? null;

        $studios = [];
        foreach ($data['studios']['edges'] ?? [] as $edge) {
            $name = $edge['node']['name'] ?? null;
            if ($name) {
                $studios[] = ['name' => $name];
            }
        }

        $genres = [];
        foreach ($data['genres'] ?? [] as $genre) {
            $genres[] = ['name' => $genre];
        }

        return [
            'mal_id' => (int) ($data['idMal'] ?? 0) ?: (int) $fallbackMalId,
            'anilist_id' => (int) ($data['id'] ?? 0),
            'title' => $title,
            'title_english' => $data['title']['english'] ?? null,
            'title_japanese' => $data['title']['native'] ?? null,
            'slug' => Str::slug($title),
            'synopsis' => ! empty($data['description']) ? strip_tags($data['description']) : null,
            'cover_image' => $data['coverImage']['extraLarge'] ?? $data['coverImage']['large'] ?? null,
            'banner_image' => $data['bannerImage'] ?? null,
            'trailer_url' => null,
            'type' => $this->mapType($data['format'] ?? ''),
            'status' => $this->mapStatus($data['status'] ?? ''),
            'season' => isset($data['season']) ? ucfirst(strtolower((string) $data['season'])) : null,
            'year' => $data['seasonYear'] ?? $data['startDate']['year'] ?? null,
            'aired_from' => $startDate,
            'aired_to' => $endDate,
            'episodes_count' => $data['episodes'] ?? null,
            'episode_duration' => $data['duration'] ?? null,
            'rating' => $this->mapRating($data['rating'] ?? ''),
            'source' => $data['source'] ?? null,
            'score' => $score !== null ? round(((int) $score) / 10, 2) : 0,
            'score_count' => 0,
            'popularity' => $data['popularity'] ?? 0,
            'rank' => null,
            'favorites_count' => $data['favourites'] ?? 0,
            'genres' => $genres,
            'studios' => $studios,
        ];
    }

    private function graphql(string $query, array $variables, int $seconds): ?array
    {
        $this->lastError = null;
        $cacheKey = 'anilist:catalog:'.md5($query.serialize($variables));

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (Throwable $e) {
            Log::warning('AniList catalog cache read failed', ['error' => $e->getMessage()]);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(8)->retry(1, 250)->post($this->graphqlUrl, [
                'query' => $query,
                'variables' => $variables,
            ]);

            if (! $response->ok()) {
                $this->lastError = 'AniList returned HTTP '.$response->status();

                return null;
            }

            $json = $response->json();
            if (! empty($json['errors'][0]['message'])) {
                $this->lastError = 'AniList GraphQL: '.$json['errors'][0]['message'];

                return null;
            }

            $data = $json['data'] ?? null;
            if (! is_array($data)) {
                $this->lastError = 'AniList returned no data';

                return null;
            }

            try {
                Cache::put($cacheKey, $data, now()->addSeconds($seconds));
            } catch (Throwable $e) {
                Log::warning('AniList catalog cache write failed', ['error' => $e->getMessage()]);
            }

            return $data;
        } catch (Throwable $e) {
            $this->lastError = class_basename($e).': '.$e->getMessage();
            Log::warning('AniList catalog request failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function dateString(?array $date): ?string
    {
        if (empty($date['year'])) {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            (int) $date['year'],
            (int) ($date['month'] ?? 1),
            (int) ($date['day'] ?? 1)
        );
    }

    private function mapType(string $format): string
    {
        return match (strtoupper($format)) {
            'MOVIE' => 'Movie',
            'OVA' => 'OVA',
            'ONA' => 'ONA',
            'SPECIAL' => 'Special',
            'MUSIC' => 'Music',
            default => 'TV',
        };
    }

    private function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'RELEASING' => 'ongoing',
            'FINISHED', 'CANCELED' => 'completed',
            default => 'upcoming',
        };
    }

    private function mapRating(string $rating): string
    {
        return match (strtoupper($rating)) {
            'G' => 'G',
            'PG' => 'PG',
            'PG13' => 'PG-13',
            'R17' => 'R',
            'R' => 'R+',
            'RX' => 'Rx',
            default => 'PG-13',
        };
    }
}
