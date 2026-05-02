<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Genre;
use App\Models\Episode;
use App\Models\AdPosition;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Hero / Trending
        $heroAnime = Cache::remember('hero_anime', 1800, fn() =>
            Anime::featured()->with(['genres'])->limit(5)->get()
        );

        if ($heroAnime->isEmpty()) {
            $heroAnime = Cache::remember('hero_anime_fallback', 1800, fn() =>
                Anime::trending()->with(['genres'])->limit(5)->get()
            );
        }

        // Trending
        $trending = Cache::remember('trending_anime', 900, fn() =>
            Anime::trending()->with(['genres'])->limit(20)->get()
        );

        // Continue watching
        $continueWatching = null;
        if ($user) {
            $continueWatching = $user->watchHistory()
                ->with(['anime', 'episode'])
                ->where('completed', false)
                ->where('progress', '>', 30)
                ->latest('watched_at')
                ->limit(10)
                ->get()
                ->filter(fn($h) => $h->episode && $h->anime);
        }

        // Top rated
        $topRated = Cache::remember('top_rated_anime', 3600, fn() =>
            Anime::topRated()->with(['genres'])->limit(20)->get()
        );

        // New episodes
        $newEpisodes = Cache::remember('new_episodes', 600, fn() =>
            Episode::with(['anime'])
                ->where('aired_at', '>=', now()->subDays(7))
                ->orderByDesc('aired_at')
                ->limit(20)
                ->get()
        );

        // By genre sections (pick 3 random popular genres)
        $genreSections = Cache::remember('genre_sections', 3600, fn() => [
            'Action'  => Anime::byGenre('action')->with('genres')->latest()->limit(12)->get(),
            'Romance' => Anime::byGenre('romance')->with('genres')->latest()->limit(12)->get(),
            'Fantasy' => Anime::byGenre('fantasy')->with('genres')->latest()->limit(12)->get(),
        ]);

        // Upcoming
        $upcoming = Cache::remember('upcoming_anime', 3600, fn() =>
            Anime::where('status', 'upcoming')->with('genres')->orderBy('aired_from')->limit(10)->get()
        );

        // Genres for sidebar
        $genres = Cache::remember('genres_nav', 86400, fn() =>
            Genre::where('is_active', true)->orderBy('sort_order')->get()
        );

        // Ads
        $ads = [];
        if (!$user || !$user->isPremium()) {
            $positions = ['homepage_hero_banner', 'homepage_sidebar', 'homepage_in_feed'];
            foreach ($positions as $key) {
                $position = AdPosition::where('key', $key)->with(['ads' => fn($q) => $q->where('is_active', true)])->first();
                if ($position) {
                    $ad = $position->getActiveAd($user);
                    if ($ad) $ads[$key] = $ad;
                }
            }
        }

        $heroJson = $heroAnime->map(function($a) {
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
}