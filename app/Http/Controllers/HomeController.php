<?php

namespace App\Http\Controllers;

use App\Models\AdPosition;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\Genre;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $heroAnime = $this->rememberSafely('hero_anime', 1800, fn () => Anime::featured()->with(['genres'])->limit(5)->get()
        );

        if ($heroAnime !== null && $heroAnime->isEmpty()) {
            $heroAnime = $this->rememberSafely('hero_anime_fallback', 1800, fn () => Anime::trending()->with(['genres'])->limit(5)->get()
            );
        }

        $trending = $this->rememberSafely('trending_anime', 900, fn () => Anime::trending()->with(['genres'])->limit(20)->get()
        );

        $continueWatching = null;
        if ($user) {
            $continueWatching = $this->safely(fn () => $user->watchHistory()
                ->with(['anime', 'episode'])
                ->where('completed', false)
                ->where('progress', '>', 30)
                ->latest('watched_at')
                ->limit(10)
                ->get()
                ->filter(fn ($h) => $h->episode && $h->anime),
                collect(),
                'continue_watching'
            );
        }

        $topRated = $this->rememberSafely('top_rated_anime', 3600, fn () => Anime::topRated()->with(['genres'])->limit(20)->get()
        );

        $newEpisodes = $this->rememberSafely('new_episodes', 600, fn () => Episode::with(['anime'])
            ->where('aired_at', '>=', now()->subDays(7))
            ->orderByDesc('aired_at')
            ->limit(20)
            ->get()
        );

        $genreSections = $this->rememberSafely('genre_sections', 3600, fn () => [
            'Action' => Anime::byGenre('action')->with('genres')->latest()->limit(12)->get(),
            'Romance' => Anime::byGenre('romance')->with('genres')->latest()->limit(12)->get(),
            'Fantasy' => Anime::byGenre('fantasy')->with('genres')->latest()->limit(12)->get(),
        ]);

        $upcoming = $this->rememberSafely('upcoming_anime', 3600, fn () => Anime::where('status', 'upcoming')->with('genres')->orderBy('aired_from')->limit(10)->get()
        );

        $genres = $this->rememberSafely('genres_nav', 86400, fn () => Genre::where('is_active', true)->orderBy('sort_order')->get()
        );

        $ads = [];
        if (! $user || ! $user->isPremium()) {
            $positions = $this->safely(fn () => AdPosition::whereIn('key', ['homepage_hero_banner', 'homepage_sidebar', 'homepage_in_feed'])
                ->with(['ads' => fn ($q) => $q->where('is_active', true)])
                ->get()
                ->keyBy('key'),
                collect(),
                'home_ads'
            );

            foreach ($positions as $key => $position) {
                $ad = $this->safely(fn () => $position->getActiveAd($user), null, "home_ad:{$key}");
                if ($ad) {
                    $ads[$key] = $ad;
                }
            }
        }

        $heroAnime = $heroAnime ?? collect();
        $trending = $trending ?? collect();
        $topRated = $topRated ?? collect();
        $newEpisodes = $newEpisodes ?? collect();
        $genreSections = $genreSections ?? [];
        $upcoming = $upcoming ?? collect();
        $genres = $genres ?? collect();
        $continueWatching = $continueWatching ?? collect();

        $heroJson = $heroAnime->map(function ($a) {
            return [
                'title' => $a->title_english ?? $a->title,
                'synopsis' => $a->synopsis,
                'slug' => $a->slug,
                'cover_image' => $a->cover_image,
            ];
        });

        return view('home', compact(
            'heroAnime', 'trending', 'continueWatching', 'topRated',
            'newEpisodes', 'genreSections', 'upcoming', 'genres', 'ads', 'heroJson'
        ));
    }

    private function rememberSafely(string $key, int $seconds, callable $callback): mixed
    {
        return $this->safely(fn () => Cache::remember($key, $seconds, $callback), null, $key);
    }

    private function safely(callable $callback, mixed $fallback = null, ?string $context = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::warning('Homepage data lookup failed', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}
