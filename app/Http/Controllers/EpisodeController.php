<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\WatchHistory;
use App\Models\AdPosition;
use App\Models\Comment;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    public function show(Anime $anime, Episode $episode)
    {
        $episode->load(['sources', 'subtitles', 'anime']);

        // Verify episode belongs to anime
        abort_if($episode->anime_id !== $anime->id, 404);

        // Increment view
        $episode->increment('views_count');

        // User progress
        $progress = null;
        if (auth()->check()) {
            $progress = WatchHistory::where('user_id', auth()->id())
                ->where('episode_id', $episode->id)
                ->first();
        }

        // Navigation episodes
        $nextEpisode     = $episode->next_episode;
        $previousEpisode = $episode->previous_episode;

        // All episodes for playlist
        $allEpisodes = Episode::where('anime_id', $anime->id)
            ->orderBy('number')
            ->select(['id', 'number', 'title', 'thumbnail', 'duration', 'aired_at', 'is_filler'])
            ->get();

        // Top-level comments
        $comments = Comment::where('episode_id', $episode->id)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->with(['user', 'replies.user'])
            ->withCount('replies')
            ->latest()
            ->paginate(20);

        // Ads
        $user = auth()->user();
        $ads  = [];
        if (!$user || !$user->isPremium()) {
            $position = AdPosition::where('key', 'episode_player_preroll')->first();
            if ($position) $ads['preroll'] = $position->getActiveAd($user);

            $position = AdPosition::where('key', 'episode_overlay')->first();
            if ($position) $ads['overlay'] = $position->getActiveAd($user);

            $position = AdPosition::where('key', 'episode_sidebar')->first();
            if ($position) $ads['sidebar'] = $position->getActiveAd($user);
        }

        return view('episode.show', compact(
            'anime', 'episode', 'progress', 'nextEpisode', 'previousEpisode',
            'allEpisodes', 'comments', 'ads'
        ));
    }

    public function saveProgress(Request $request, Episode $episode)
    {
        $request->validate([
            'progress' => 'required|integer|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        if (!auth()->check()) {
            return response()->json(['ok' => false], 401);
        }

        $completed = $request->progress >= ($request->duration * 0.90); // 90% = completed

        WatchHistory::updateOrCreate(
            [
                'user_id'    => auth()->id(),
                'episode_id' => $episode->id,
            ],
            [
                'anime_id'   => $episode->anime_id,
                'progress'   => $request->progress,
                'duration'   => $request->duration,
                'completed'  => $completed,
                'watched_at' => now(),
            ]
        );

        return response()->json(['ok' => true, 'completed' => $completed]);
    }

    public function markCompleted(Episode $episode)
    {
        if (!auth()->check()) return response()->json(['ok' => false], 401);

        WatchHistory::updateOrCreate(
            ['user_id' => auth()->id(), 'episode_id' => $episode->id],
            ['anime_id' => $episode->anime_id, 'progress' => $episode->duration ?? 1, 'duration' => $episode->duration ?? 1, 'completed' => true, 'watched_at' => now()]
        );

        return response()->json(['ok' => true]);
    }
}