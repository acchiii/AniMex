<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\EpisodeSource;
use App\Models\Genre;
use App\Models\Studio;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminAnimeController extends Controller
{
    public function index()
    {
        $anime = Anime::withCount(['episodes'])->with('studio')->latest()->paginate(20);
        return view('admin.anime.index', compact('anime'));
    }

    public function create()
    {
        $genres = Genre::orderBy('name')->get();
        $studios = Studio::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.anime.create', compact('genres', 'studios', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_english' => 'nullable|string|max:255',
            'title_japanese' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:anime,slug',
            'synopsis' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'banner_image' => 'nullable|image|max:2048',
            'trailer_url' => 'nullable|url',
            'type' => 'required|in:TV,Movie,OVA,ONA,Special,Music',
            'status' => 'required|in:ongoing,completed,upcoming,hiatus',
            'season' => 'nullable|in:Winter,Spring,Summer,Fall',
            'year' => 'nullable|integer|min:1900|max:2099',
            'aired_from' => 'nullable|date',
            'aired_to' => 'nullable|date|after_or_equal:aired_from',
            'episodes_count' => 'nullable|integer|min:0',
            'episode_duration' => 'nullable|integer|min:0',
            'rating' => 'required|in:G,PG,PG-13,R,R+,Rx',
            'source' => 'nullable|string|max:255',
            'studio_id' => 'nullable|exists:studios,id',
            'score' => 'nullable|numeric|min:0|max:10',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_subbed' => 'boolean',
            'is_dubbed' => 'boolean',
            'is_premium_only' => 'boolean',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        if ($validated['slug'] === null) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $booleans = ['is_featured', 'is_trending', 'is_subbed', 'is_dubbed', 'is_premium_only'];
        foreach ($booleans as $bool) {
            $validated[$bool] = $request->boolean($bool);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('anime/covers', 'public');
        }

        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')->store('anime/banners', 'public');
        }

        $anime = Anime::create($validated);

        if ($request->filled('genres')) {
            $anime->genres()->sync($request->genres);
        }
        if ($request->filled('tags')) {
            $anime->tags()->sync($request->tags);
        }

        Cache::forget('hero_anime');
        Cache::forget('hero_anime_fallback');
        Cache::forget('trending_anime');
        Cache::forget('top_rated_anime');
        Cache::forget('genre_sections');
        Cache::forget('upcoming_anime');

        return redirect()->route('admin.anime.index')->with('success', 'Anime created successfully.');
    }

    public function edit(Anime $anime)
    {
        $genres = Genre::orderBy('name')->get();
        $studios = Studio::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $anime->load('genres', 'tags');
        return view('admin.anime.edit', compact('anime', 'genres', 'studios', 'tags'));
    }

    public function update(Request $request, Anime $anime)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_english' => 'nullable|string|max:255',
            'title_japanese' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:anime,slug,' . $anime->id,
            'synopsis' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'banner_image' => 'nullable|image|max:2048',
            'trailer_url' => 'nullable|url',
            'type' => 'required|in:TV,Movie,OVA,ONA,Special,Music',
            'status' => 'required|in:ongoing,completed,upcoming,hiatus',
            'season' => 'nullable|in:Winter,Spring,Summer,Fall',
            'year' => 'nullable|integer|min:1900|max:2099',
            'aired_from' => 'nullable|date',
            'aired_to' => 'nullable|date|after_or_equal:aired_from',
            'episodes_count' => 'nullable|integer|min:0',
            'episode_duration' => 'nullable|integer|min:0',
            'rating' => 'required|in:G,PG,PG-13,R,R+,Rx',
            'source' => 'nullable|string|max:255',
            'studio_id' => 'nullable|exists:studios,id',
            'score' => 'nullable|numeric|min:0|max:10',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_subbed' => 'boolean',
            'is_dubbed' => 'boolean',
            'is_premium_only' => 'boolean',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        if ($validated['slug'] === null) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $booleans = ['is_featured', 'is_trending', 'is_subbed', 'is_dubbed', 'is_premium_only'];
        foreach ($booleans as $bool) {
            $validated[$bool] = $request->boolean($bool);
        }

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('anime/covers', 'public');
        }

        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')->store('anime/banners', 'public');
        }

        $anime->update($validated);

        if ($request->filled('genres')) {
            $anime->genres()->sync($request->genres);
        }
        if ($request->filled('tags')) {
            $anime->tags()->sync($request->tags);
        }

        Cache::forget('hero_anime');
        Cache::forget('hero_anime_fallback');
        Cache::forget('trending_anime');
        Cache::forget('top_rated_anime');
        Cache::forget('genre_sections');
        Cache::forget('upcoming_anime');

        return redirect()->route('admin.anime.index')->with('success', 'Anime updated successfully.');
    }

    public function destroy(Anime $anime)
    {
        $anime->delete();

        Cache::forget('hero_anime');
        Cache::forget('hero_anime_fallback');
        Cache::forget('trending_anime');
        Cache::forget('top_rated_anime');
        Cache::forget('genre_sections');
        Cache::forget('upcoming_anime');

        return redirect()->route('admin.anime.index')->with('success', 'Anime deleted successfully.');
    }

    public function episodes(Anime $anime)
    {
        $episodes = $anime->episodes()->with('sources')->get();
        return view('admin.anime.episodes', compact('anime', 'episodes'));
    }

    public function storeEpisode(Request $request, Anime $anime)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'number' => 'required|integer|min:1',
            'synopsis' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'duration' => 'nullable|integer|min:0',
            'aired_at' => 'nullable|date',
            'is_filler' => 'boolean',
            'is_recap' => 'boolean',
            'is_subbed' => 'boolean',
            'is_dubbed' => 'boolean',
            'is_premium_only' => 'boolean',
        ]);

        $booleans = ['is_filler', 'is_recap', 'is_subbed', 'is_dubbed', 'is_premium_only'];
        foreach ($booleans as $bool) {
            $validated[$bool] = $request->boolean($bool);
        }

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('episodes/thumbnails', 'public');
        }

        $validated['anime_id'] = $anime->id;

        $episode = Episode::create($validated);

        return redirect()->route('admin.anime.episodes', $anime)->with('success', 'Episode created successfully.');
    }

    public function updateEpisode(Request $request, Anime $anime, Episode $episode)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'number' => 'required|integer|min:1',
            'synopsis' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'duration' => 'nullable|integer|min:0',
            'aired_at' => 'nullable|date',
            'is_filler' => 'boolean',
            'is_recap' => 'boolean',
            'is_subbed' => 'boolean',
            'is_dubbed' => 'boolean',
            'is_premium_only' => 'boolean',
        ]);

        $booleans = ['is_filler', 'is_recap', 'is_subbed', 'is_dubbed', 'is_premium_only'];
        foreach ($booleans as $bool) {
            $validated[$bool] = $request->boolean($bool);
        }

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('episodes/thumbnails', 'public');
        }

        $episode->update($validated);

        return redirect()->route('admin.anime.episodes', $anime)->with('success', 'Episode updated successfully.');
    }

    public function destroyEpisode(Anime $anime, Episode $episode)
    {
        $episode->delete();
        return redirect()->route('admin.anime.episodes', $anime)->with('success', 'Episode deleted successfully.');
    }

    public function storeSource(Request $request, Anime $anime, Episode $episode)
    {
        abort_if($episode->anime_id !== $anime->id, 404);

        $validated = $request->validate([
            'url' => 'required|url',
            'quality' => 'required|in:360p,480p,720p,1080p,4K',
            'type' => 'required|in:mp4,hls,embed',
            'language' => 'required|in:sub,dub',
            'label' => 'nullable|string|max:255',
            'headers' => 'nullable|string',
        ]);

        $headers = null;
        if ($request->filled('headers')) {
            $decoded = json_decode($request->headers, true);
            if (is_array($decoded)) {
                $headers = $decoded;
            }
        }

        $source = EpisodeSource::create([
            'episode_id' => $episode->id,
            'video_server_id' => null,
            'label' => $validated['label'] ?? $validated['quality'],
            'quality' => $validated['quality'],
            'url' => $validated['url'],
            'headers' => $headers,
            'type' => $validated['type'],
            'language' => $validated['language'],
            'is_active' => true,
            'sort_order' => $episode->sources()->max('sort_order') + 1,
        ]);

        return redirect()->route('admin.anime.episodes', $anime)->with('success', 'Source added successfully.');
    }

    public function destroySource(Anime $anime, Episode $episode, EpisodeSource $source)
    {
        abort_if($episode->anime_id !== $anime->id, 404);
        abort_if($source->episode_id !== $episode->id, 404);

        $source->delete();

        return redirect()->route('admin.anime.episodes', $anime)->with('success', 'Source deleted successfully.');
    }
}
