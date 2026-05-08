@extends('layouts.admin')

@section('title', 'Add Anime')
@section('page-title', 'Add New Anime')

@section('content')
<form action="{{ route('admin.anime.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                @error('title')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Title (English)</label>
                <input type="text" name="title_english" value="{{ old('title_english') }}" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Title (Japanese)</label>
                <input type="text" name="title_japanese" value="{{ old('title_japanese') }}" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Slug <span class="text-gray-500 dark:text-gray-400">(auto-generated if empty)</span></label>
                <input type="text" name="slug" value="{{ old('slug') }}" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Synopsis</label>
            <textarea name="synopsis" rows="4" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">{{ old('synopsis') }}</textarea>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">Images</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Cover Image</label>
                <input type="file" name="cover_image" accept="image/*" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm file:bg-gray-100 dark:file:bg-gray-700 file:border-0 file:rounded file:px-3 file:py-1 file:text-sm file:text-gray-700 dark:file:text-gray-300">
                @error('cover_image')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Banner Image</label>
                <input type="file" name="banner_image" accept="image/*" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm file:bg-gray-100 dark:file:bg-gray-700 file:border-0 file:rounded file:px-3 file:py-1 file:text-sm file:text-gray-700 dark:file:text-gray-300">
                @error('banner_image')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Trailer URL</label>
            <input type="url" name="trailer_url" value="{{ old('trailer_url') }}" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Type *</label>
                <select name="type" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Select type</option>
                    @foreach(['TV', 'Movie', 'OVA', 'ONA', 'Special', 'Music'] as $t)
                        <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
                @error('type')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Status *</label>
                <select name="status" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Select status</option>
                    @foreach(['ongoing', 'completed', 'upcoming', 'hiatus'] as $s)
                        <option value="{{ $s }}" {{ old('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                @error('status')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Rating *</label>
                <select name="rating" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Select rating</option>
                    @foreach(['G', 'PG', 'PG-13', 'R', 'R+', 'Rx'] as $r)
                        <option value="{{ $r }}" {{ old('rating') === $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                </select>
                @error('rating')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Season</label>
                <select name="season" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">None</option>
                    @foreach(['Winter', 'Spring', 'Summer', 'Fall'] as $s)
                        <option value="{{ $s }}" {{ old('season') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Year</label>
                <input type="number" name="year" value="{{ old('year') }}" min="1900" max="2099" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Episodes Count</label>
                <input type="number" name="episodes_count" value="{{ old('episodes_count') }}" min="0" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Episode Duration (min)</label>
                <input type="number" name="episode_duration" value="{{ old('episode_duration') }}" min="0" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Score</label>
                <input type="number" name="score" value="{{ old('score') }}" step="0.01" min="0" max="10" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Studio</label>
                <select name="studio_id" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">None</option>
                    @foreach($studios as $studio)
                        <option value="{{ $studio->id }}" {{ old('studio_id') == $studio->id ? 'selected' : '' }}>{{ $studio->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 pt-2">
            @foreach([
                ['name'=>'is_featured','label'=>'Featured','checked'=>false],
                ['name'=>'is_trending','label'=>'Trending','checked'=>false],
                ['name'=>'is_subbed','label'=>'Subbed','checked'=>true],
                ['name'=>'is_dubbed','label'=>'Dubbed','checked'=>false],
                ['name'=>'is_premium_only','label'=>'Premium','checked'=>false],
            ] as $opt)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="{{ $opt['name'] }}" value="1" {{ old($opt['name'], $opt['checked']) ? 'checked' : '' }} class="rounded bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-purple-600 focus:ring-purple-600">
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $opt['label'] }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 space-y-4">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">Genres</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($genres as $genre)
                <label class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-750">
                    <input type="checkbox" name="genres[]" value="{{ $genre->id }}" {{ in_array($genre->id, old('genres', [])) ? 'checked' : '' }} class="rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-purple-600 focus:ring-purple-600">
                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $genre->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2.5 rounded-lg text-sm font-medium transition">Create Anime</button>
        <a href="{{ route('admin.anime.index') }}" class="px-6 py-2.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">Cancel</a>
    </div>
</form>
@endsection

