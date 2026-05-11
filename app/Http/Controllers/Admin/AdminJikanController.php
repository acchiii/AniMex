<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Studio;
use App\Models\Tag;
use App\Services\AnilistVideoSourceService;
use App\Services\JikanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdminJikanController extends Controller
{
    protected JikanService $jikan;

    public function __construct(JikanService $jikan)
    {
        $this->jikan = $jikan;
    }

    private function lookupAnilistId(int $malId): ?int
    {
        $query = <<<'GQL'
            query ($idMal: Int) {
                Media(idMal: $idMal, type: ANIME) {
                    id
                }
            }
GQL;

        try {
            $resp = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(10)->post('https://graphql.anilist.co', [
                'query' => $query,
                'variables' => ['idMal' => $malId],
            ]);

            if (!$resp->ok()) return null;

            $json = $resp->json();
            return $json['data']['Media']['id'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $page = (int) $request->get('page', 1);

        if (empty($query)) {
            return view('admin.jikan.search');
        }

        $results = $this->jikan->searchAnime($query, $page, 20);

        $parsed = [];
        foreach ($results as $item) {
            $parsed[] = [
                'mal_id' => $item['mal_id'],
                'title' => $item['title'],
                'title_english' => $item['title_english'] ?? null,
                'image' => $item['images']['jpg']['large_image_url'] ?? $item['images']['jpg']['image_url'] ?? null,
                'type' => $item['type'] ?? 'Unknown',
                'episodes' => $item['episodes'] ?? '?',
                'score' => $item['score'] ?? 'N/A',
                'status' => $item['status'] ?? 'Unknown',
            ];
        }

        return view('admin.jikan.search', compact('parsed', 'query', 'page'));
    }

    public function import(int $malId)
    {
        $data = $this->jikan->getAnime($malId);
        if (!$data) {
            return redirect()->back()->with('error', 'Failed to fetch anime data.');
        }

        $parsed = $this->jikan->parseAnimeData($data);

        $existing = Anime::where('mal_id', $malId)->first();
        if ($existing) {
            return redirect()->route('admin.anime.edit', $existing)->with('info', 'This anime already exists in your database.');
        }

        $existingBySlug = Anime::where('slug', $parsed['slug'])->first();
        if ($existingBySlug) {
            $parsed['slug'] = $parsed['slug'] . '-' . $malId;
        }

        $studioId = null;
        if (!empty($parsed['studios'])) {
            $studio = Studio::firstOrCreate(['name' => $parsed['studios'][0]['name']], [
                'slug' => \Illuminate\Support\Str::slug($parsed['studios'][0]['name']),
            ]);
            $studioId = $studio->id;
        }

        $animeData = [
            'mal_id' => $parsed['mal_id'],
            'title' => $parsed['title'],
            'title_english' => $parsed['title_english'],
            'title_japanese' => $parsed['title_japanese'],
            'slug' => $parsed['slug'],
            'synopsis' => $parsed['synopsis'],
            'cover_image' => $parsed['cover_image'],
            'banner_image' => $parsed['banner_image'],
            'trailer_url' => $parsed['trailer_url'],
            'type' => $parsed['type'],
            'status' => $parsed['status'],
            'season' => $parsed['season'],
            'year' => $parsed['year'],
            'aired_from' => $parsed['aired_from'],
            'aired_to' => $parsed['aired_to'],
            'episodes_count' => $parsed['episodes_count'],
            'episode_duration' => $parsed['episode_duration'],
            'rating' => $parsed['rating'],
            'source' => $parsed['source'],
            'studio_id' => $studioId,
            'score' => $parsed['score'],
            'score_count' => $parsed['score_count'],
            'popularity' => $parsed['popularity'],
            'rank' => $parsed['rank'],
            'favorites_count' => $parsed['favorites_count'],
        ];

        $anime = null;
        $slugAttempt = 0;
        while ($anime === null) {
            try {
                $anime = Anime::create($animeData);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $slugAttempt++;
                if ($slugAttempt > 10) throw $e;
                $animeData['slug'] = $parsed['slug'] . '-' . $malId . ($slugAttempt > 1 ? '-' . $slugAttempt : '');
            }
        }

        $genreIds = [];
        foreach ($parsed['genres'] as $genreData) {
            $genre = Genre::firstOrCreate(['name' => $genreData['name']], [
                'slug' => \Illuminate\Support\Str::slug($genreData['name']),
            ]);
            $genreIds[] = $genre->id;
        }
        $anime->genres()->sync($genreIds);

        $anilistId = $this->lookupAnilistId($malId);
        if ($anilistId) {
            $anime->anilist_id = $anilistId;
            $anime->save();

            try {
                $service = app(AnilistVideoSourceService::class);
                $result = $service->fetchEpisodeList($anilistId, $anime->title);

                if (empty($result['error'])) {
                    $episodesData = $result['episodes'] ?? [];
                    $created = 0;

                    foreach ($episodesData as $ep) {
                        $number = (int) ($ep['number'] ?? 0);
                        if ($number <= 0) continue;
                        if ($anime->episodes()->where('number', $number)->exists()) continue;

                        Episode::create([
                            'anime_id' => $anime->id,
                            'number' => $number,
                            'title' => $ep['title'] ?? null,
                            'synopsis' => $ep['description'] ?? null,
                            'thumbnail' => $ep['image'] ?? null,
                            'aired_at' => isset($ep['releaseDate']) && $ep['releaseDate'] ? $ep['releaseDate'] : null,
                        ]);
                        $created++;
                    }

                    Cache::forget('hero_anime');
                    Cache::forget('hero_anime_fallback');
                    Cache::forget('trending_anime');
                    Cache::forget('top_rated_anime');
                    Cache::forget('genre_sections');
                    Cache::forget('upcoming_anime');

                    return redirect()->route('admin.anime.edit', $anime)->with('success', "Anime imported with {$created} episodes.");
                }
            } catch (\Exception $e) {
                // episode fetch failed, import still succeeded
            }
        }

        Cache::forget('hero_anime');
        Cache::forget('hero_anime_fallback');
        Cache::forget('trending_anime');
        Cache::forget('top_rated_anime');
        Cache::forget('genre_sections');
        Cache::forget('upcoming_anime');

        return redirect()->route('admin.anime.edit', $anime)->with('success', 'Anime imported from Jikan successfully.');
    }
}
