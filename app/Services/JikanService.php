<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class JikanService
{
    private string $baseUrl = 'https://api.jikan.moe/v4';

    public function searchAnime(string $query, int $page = 1, int $limit = 10): array
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/anime", [
            'q' => $query,
            'page' => $page,
            'limit' => $limit,
            'sfw' => true,
        ]);

        return $response->json('data') ?? [];
    }

    public function getAnime(int $malId): ?array
    {
        $response = Http::get("{$this->baseUrl}/anime/{$malId}/full");
        return $response->json('data');
    }

    public function getTopAnime(string $filter = 'airing', int $page = 1, int $limit = 25): array
    {
        $params = ['page' => $page, 'limit' => $limit, 'sfw' => true];
        if ($filter && $filter !== 'all') {
            $params['filter'] = $filter;
        }

        $response = Http::get("{$this->baseUrl}/top/anime", $params);
        return $response->json('data') ?? [];
    }

    public function parseAnimeData(array $data): array
    {
        $title = $data['titles'][0]['title'] ?? $data['title'] ?? 'Unknown';
        $titleEnglish = collect($data['titles'] ?? [])
            ->firstWhere('type', 'English')['title'] ?? null;
        $titleJapanese = collect($data['titles'] ?? [])
            ->firstWhere('type', 'Japanese')['title'] ?? null;

        $status = match($data['status'] ?? '') {
            'Currently Airing' => 'ongoing',
            'Finished Airing' => 'completed',
            'Not yet aired' => 'upcoming',
            default => 'upcoming',
        };

        $type = match($data['type'] ?? '') {
            'TV' => 'TV',
            'Movie' => 'Movie',
            'OVA' => 'OVA',
            'ONA' => 'ONA',
            'Special' => 'Special',
            'Music' => 'Music',
            default => 'TV',
        };

        $season = isset($data['season']) ? ucfirst(strtolower($data['season'])) : null;
        $year = $data['year'] ?? null;

        $airedFrom = $data['aired']['from'] ?? null;
        $airedTo = $data['aired']['to'] ?? null;

        $genres = [];
        foreach ($data['genres'] ?? [] as $genre) {
            $genres[] = ['name' => $genre['name']];
        }

        $studios = [];
        foreach ($data['studios'] ?? [] as $studio) {
            $studios[] = ['name' => $studio['name']];
        }

        $coverImage = $data['images']['jpg']['large_image_url'] 
            ?? $data['images']['jpg']['image_url'] 
            ?? null;

        return [
            'mal_id' => $data['mal_id'],
            'title' => $title,
            'title_english' => $titleEnglish,
            'title_japanese' => $titleJapanese,
            'slug' => Str::slug($title),
            'synopsis' => $data['synopsis'] ?? null,
            'cover_image' => $coverImage,
            'banner_image' => null,
            'trailer_url' => $data['trailer']['url'] ?? null,
            'type' => $type,
            'status' => $status,
            'season' => $season,
            'year' => $year,
            'aired_from' => $airedFrom ? date('Y-m-d', strtotime($airedFrom)) : null,
            'aired_to' => $airedTo ? date('Y-m-d', strtotime($airedTo)) : null,
            'episodes_count' => $data['episodes'] ?? null,
            'episode_duration' => $data['duration'] ? $this->parseDuration($data['duration']) : null,
            'rating' => $this->mapRating($data['rating'] ?? ''),
            'source' => $data['source'] ?? null,
            'score' => $data['score'] ?? 0,
            'score_count' => $data['scored_by'] ?? 0,
            'popularity' => $data['popularity'] ?? 0,
            'rank' => $data['rank'] ?? null,
            'favorites_count' => $data['favorites'] ?? 0,
            'genres' => $genres,
            'studios' => $studios,
        ];
    }

    private function parseDuration(string $duration): ?int
    {
        if (preg_match('/(\d+)\s*hr\s*(\d*)\s*min/i', $duration, $matches)) {
            return ((int)$matches[1] * 60) + (int)($matches[2] ?? 0);
        }
        if (preg_match('/(\d+)\s*min/i', $duration, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    private function mapRating(string $rating): string
    {
        return match($rating) {
            'G - All Ages' => 'G',
            'PG - Children' => 'PG',
            'PG-13 - Teens 13 or older' => 'PG-13',
            'R - 17+ (violence & profanity)' => 'R',
            'R+ - Mild Nudity' => 'R+',
            'Rx - Hentai' => 'Rx',
            default => 'PG-13',
        };
    }
}
