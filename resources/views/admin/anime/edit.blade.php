@extends('layouts.admin')

@section('title', 'Edit Anime')
@section('page-title', 'Edit: ' . ($anime->title_english ?: $anime->title))

@section('content')
<form action="{{ route('admin.anime.update', $anime) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title', $anime->title) }}" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Title (English)</label>
                <input type="text" name="title_english" value="{{ old('title_english', $anime->title_english) }}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Title (Japanese)</label>
                <input type="text" name="title_japanese" value="{{ old('title_japanese', $anime->title_japanese) }}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $anime->slug) }}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Synopsis</label>
            <textarea name="synopsis" rows="4" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">{{ old('synopsis', $anime->synopsis) }}</textarea>
        </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium">Images</h3>
        @if($anime->cover_image)
            <div class="flex items-center gap-4">
                <img src="{{ str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image) }}" class="w-20 h-28 rounded-lg object-cover">
                <div>
                    <p class="text-sm text-gray-400">Current cover image</p>
                    <p class="text-xs text-gray-600">{{ $anime->cover_image }}</p>
                </div>
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Cover Image <span class="text-gray-600">(upload to replace)</span></label>
                <input type="file" name="cover_image" accept="image/*" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm file:bg-gray-700 file:border-0 file:rounded file:px-3 file:py-1 file:text-sm file:text-gray-300">
                @error('cover_image')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Banner Image <span class="text-gray-600">(upload to replace)</span></label>
                <input type="file" name="banner_image" accept="image/*" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm file:bg-gray-700 file:border-0 file:rounded file:px-3 file:py-1 file:text-gray-300 file:text-sm">
                @error('banner_image')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Trailer URL</label>
            <input type="url" name="trailer_url" value="{{ old('trailer_url', $anime->trailer_url) }}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium">Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Type *</label>
                <select name="type" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Select type</option>
                    @foreach(['TV', 'Movie', 'OVA', 'ONA', 'Special', 'Music'] as $t)
                        <option value="{{ $t }}" {{ old('type', $anime->type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Status *</label>
                <select name="status" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Select status</option>
                    @foreach(['ongoing', 'completed', 'upcoming', 'hiatus'] as $s)
                        <option value="{{ $s }}" {{ old('status', $anime->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Rating *</label>
                <select name="rating" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Select rating</option>
                    @foreach(['G', 'PG', 'PG-13', 'R', 'R+', 'Rx'] as $r)
                        <option value="{{ $r }}" {{ old('rating', $anime->rating) === $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Season</label>
                <select name="season" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">None</option>
                    @foreach(['Winter', 'Spring', 'Summer', 'Fall'] as $s)
                        <option value="{{ $s }}" {{ old('season', $anime->season) === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Year</label>
                <input type="number" name="year" value="{{ old('year', $anime->year) }}" min="1900" max="2099" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Episodes Count</label>
                <input type="number" name="episodes_count" value="{{ old('episodes_count', $anime->episodes_count) }}" min="0" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Episode Duration (min)</label>
                <input type="number" name="episode_duration" value="{{ old('episode_duration', $anime->episode_duration) }}" min="0" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Score</label>
                <input type="number" name="score" value="{{ old('score', $anime->score) }}" step="0.01" min="0" max="10" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Studio</label>
                <select name="studio_id" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">None</option>
                    @foreach($studios as $studio)
                        <option value="{{ $studio->id }}" {{ old('studio_id', $anime->studio_id) == $studio->id ? 'selected' : '' }}>{{ $studio->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 pt-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $anime->is_featured) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Featured</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_trending" value="1" {{ old('is_trending', $anime->is_trending) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Trending</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_subbed" value="1" {{ old('is_subbed', $anime->is_subbed) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Subbed</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_dubbed" value="1" {{ old('is_dubbed', $anime->is_dubbed) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Dubbed</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_premium_only" value="1" {{ old('is_premium_only', $anime->is_premium_only) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Premium</span>
            </label>
        </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium">Genres</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($genres as $genre)
                <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-750">
                    <input type="checkbox" name="genres[]" value="{{ $genre->id }}" {{ in_array($genre->id, old('genres', $anime->genres->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-600">
                    <span class="text-sm">{{ $genre->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2.5 rounded-lg text-sm font-medium transition">Update Anime</button>
        <a href="{{ route('admin.anime.index') }}" class="px-6 py-2.5 rounded-lg text-sm text-gray-400 hover:text-gray-200 transition">Cancel</a>
    </div>
</form>
@endsection
