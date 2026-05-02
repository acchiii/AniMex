<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\User;
use App\Models\Genre;
use App\Models\Studio;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_anime' => Anime::count(),
            'total_episodes' => Episode::count(),
            'total_users' => User::count(),
            'total_genres' => Genre::count(),
            'total_studios' => Studio::count(),
            'ongoing_anime' => Anime::where('status', 'ongoing')->count(),
            'featured_anime' => Anime::where('is_featured', true)->count(),
            'trending_anime' => Anime::where('is_trending', true)->count(),
        ];

        $recentAnime = Anime::with('studio')->latest()->limit(10)->get();
        $recentEpisodes = Episode::with('anime')->latest()->limit(10)->get();
        $recentUsers = User::latest()->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recentAnime', 'recentEpisodes', 'recentUsers'));
    }
}
