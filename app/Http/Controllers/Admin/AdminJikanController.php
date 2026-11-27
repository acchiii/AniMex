<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Studio;
use App\Services\AniListCatalogService;
use App\Services\AnilistVideoSourceService;
use App\Services\JikanService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminJikanController extends Controller
{
    protected JikanService $jikan;

    protected AniListCatalogService $anilist;

    public function __construct(JikanService $jikan, AniListCatalogService $anilist)
    {
        $this->jikan = $jikan;
        $this->anilist = $anilist;
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

            if (! $resp->ok()) {
                return null;
            }

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
        $jikanError = $this->jikan->lastError();
        $source = 'jikan';
        $error = null;

        if ($jikanError !== null && empty($results)) {
            $results = $this->anilist->search($query, $page, 20);
            $anilistError = $this->anilist->lastError();

            if ($anilistError !== null) {
                $error = 'Jikan: '.$jikanError.' | AniList: '.$anilistError;
            } else {
                $source = 'anilist';
            }
        }

        $parsed = [];
        foreach ($results as $item) {
            if (array_key_exists('anilist_id', $item)) {
                $parsed[] = $item;

                continue;
            }

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

        return view('admin.jikan.search', compact('parsed', 'query', 'page', 'source', 'error'));
    }

    public function import(Request $request, int $malId)
    {
        $directAnilistId = (int) $request->input('anilist_id', 0);
        $source = 'Jikan';

        if ($directAnilistId > 0) {
            $media = $this->anilist->getByMalId($malId);
            if (! $media) {
                return redirect()->back()->with('error', 'Failed to fetch anime data. AniList: '.$this->anilist->lastError());
            }

            $parsed = $this->anilist->parseAnimeData($media, $malId);
            $source = 'AniList';
        } else {
            $data = $this->jikan->getAnime($malId);

            if (! $data) {
                $jikanError = $this->jikan->lastError();
                $media = $this->anilist->getByMalId($malId);

                if (! $media) {
                    $anilistError = $this->anilist->lastError();
                    $detail = trim('Jikan: '.$jikanError.' | AniList: '.$anilistError);

                    return redirect()->back()->with('error', 'Failed to fetch anime data. '.$detail);
                }

                $parsed = $this->anilist->parseAnimeData($media, $malId);
                $source = 'AniList';
            } else {
                $parsed = $this->jikan->parseAnimeData($data);
            }
        }

        $existing = Anime::where('mal_id', $malId)->first();
        if ($existing) {
            return redirect()->route('admin.anime.edit', $existing)->with('info', 'This anime already exists in your database.');
        }

        $existingBySlug = Anime::where('slug', $parsed['slug'])->first();
        if ($existingBySlug) {
            $parsed['slug'] = $parsed['slug'].'-'.$malId;
        }

        $studio = $this->firstOrCreateBySlug(Studio::class, (string) ($parsed['studios'][0]['name'] ?? ''));
        $studioId = $studio?->id;

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
            } catch (UniqueConstraintViolationException $e) {
                $slugAttempt++;
                if ($slugAttempt > 10) {
                    throw $e;
                }
                $animeData['slug'] = $parsed['slug'].'-'.$malId.($slugAttempt > 1 ? '-'.$slugAttempt : '');
            }
        }

        $genreIds = [];
        foreach ($parsed['genres'] as $genreData) {
            $genre = $this->firstOrCreateBySlug(Genre::class, (string) ($genreData['name'] ?? ''));
            if ($genre) {
                $genreIds[] = $genre->id;
            }
        }
        $anime->genres()->sync($genreIds);

        $anilistId = (int) ($parsed['anilist_id'] ?? 0) ?: $this->lookupAnilistId($malId);
        if ($anilistId) {
            $anime->anilist_id = $anilistId;
            $anime->save();

            try {
                $service = app(AnilistVideoSourceService::class);
                $result = $service->fetchEpisodeList($anilistId, $anime->title);

                if (empty($result['error'])) {
                    $episodesData = $result['episodes'] ?? [];
                    $rows = [];

                    foreach ($episodesData as $ep) {
                        $rows[] = [
                            'number' => (int) ($ep['number'] ?? 0),
                            'title' => $ep['title'] ?? null,
                            'synopsis' => $ep['description'] ?? null,
                            'thumbnail' => $ep['image'] ?? null,
                            'aired_at' => ! empty($ep['releaseDate']) ? substr((string) $ep['releaseDate'], 0, 10) : null,
                        ];
                    }

                    $created = $this->insertMissingEpisodes($anime, $rows);

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

        $placeholders = $this->createPlaceholderEpisodes($anime, (int) ($parsed['episodes_count'] ?? 0));

        Cache::forget('hero_anime');
        Cache::forget('hero_anime_fallback');
        Cache::forget('trending_anime');
        Cache::forget('top_rated_anime');
        Cache::forget('genre_sections');
        Cache::forget('upcoming_anime');

        $message = "Anime imported from {$source} successfully.";
        if ($placeholders > 0) {
            $message .= " {$placeholders} episode rows created from metadata.";
        }

        return redirect()->route('admin.anime.edit', $anime)->with('success', $message);
    }

    private function createPlaceholderEpisodes(Anime $anime, int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $rows = [];
        for ($number = 1; $number <= min(1000, $count); $number++) {
            $rows[] = [
                'number' => $number,
                'title' => "Episode {$number}",
            ];
        }

        return $this->insertMissingEpisodes($anime, $rows);
    }

    private function insertMissingEpisodes(Anime $anime, array $episodes): int
    {
        if (empty($episodes)) {
            return 0;
        }

        $existing = Episode::withTrashed()
            ->where('anime_id', $anime->id)
            ->pluck('number')
            ->all();
        $existing = array_map('intval', $existing);
        $timestamp = now()->format('Y-m-d H:i:s');
        $rows = [];

        foreach ($episodes as $episode) {
            $number = (int) ($episode['number'] ?? 0);
            if ($number <= 0 || in_array($number, $existing, true)) {
                continue;
            }

            $rows[] = [
                'anime_id' => $anime->id,
                'number' => $number,
                'title' => $episode['title'] ?? null,
                'synopsis' => $episode['synopsis'] ?? null,
                'thumbnail' => $episode['thumbnail'] ?? null,
                'aired_at' => $episode['aired_at'] ?? null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
            $existing[] = $number;
        }

        if (empty($rows)) {
            return 0;
        }

        Episode::insertOrIgnore($rows);

        return count($rows);
    }

    private function firstOrCreateBySlug(string $modelClass, string $name): ?Model
    {
        $name = trim($name);
        $slug = Str::slug($name);
        if ($slug === '') {
            return null;
        }

        try {
            return $modelClass::firstOrCreate(['slug' => $slug], ['name' => $name]);
        } catch (UniqueConstraintViolationException) {
            return $modelClass::where('slug', $slug)->first();
        }
    }
}
