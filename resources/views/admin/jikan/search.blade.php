@extends('layouts.admin')

@section('title', 'Import Anime')
@section('page-title', 'Import from Jikan/MyAnimeList')

@section('header-actions')
<a href="{{ route('admin.anime.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 text-sm transition">&larr; Back to Anime</a>
@endsection

@section('content')
<form action="{{ route('admin.import.search') }}" method="GET" class="mb-6">
    <div class="flex gap-3">
        <input
            type="text"
            name="q"
            value="{{ $query ?? '' }}"
            placeholder="Search anime..."
            required
            class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-600"
        >
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2.5 rounded-lg text-sm font-medium transition">Search</button>
    </div>
</form>

@if(! empty($error))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/40 p-4 text-sm text-red-800 dark:text-red-200">
        <p class="font-semibold">Both anime catalogs failed.</p>
        <p class="mt-1 break-words">{{ $error }}</p>
    </div>
@elseif(isset($query) && ($source ?? 'jikan') === 'anilist')
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/40 p-4 text-sm text-amber-800 dark:text-amber-200">
        Jikan is unavailable; showing AniList results instead.
    </div>
@endif

@if(isset($parsed) && count($parsed) > 0)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            @foreach($parsed as $item)
                <div class="flex items-center gap-4 px-5 py-4">
                    <img
                        src="{{ $item['image'] }}"
                        class="w-16 h-24 rounded-lg object-cover flex-shrink-0 bg-gray-100 dark:bg-gray-800"
                        alt="{{ $item['title'] }}"
                    >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['title_english'] ?: $item['title'] }}</p>
                        @if($item['title'] !== $item['title_english'])
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $item['title'] }}</p>
                        @endif
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $item['type'] }} &middot; {{ $item['episodes'] }} eps &middot; Score: {{ $item['score'] }} &middot; {{ $item['status'] }}
                        </p>
                    </div>
                    <form action="{{ route('admin.import.anime', $item['mal_id']) }}" method="POST">
                        @csrf
                        @if(($source ?? 'jikan') === 'anilist' && ! empty($item['anilist_id']))
                            <input type="hidden" name="anilist_id" value="{{ $item['anilist_id'] }}">
                        @endif
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">Import</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@elseif(isset($query) && empty($error))
    <div class="text-center py-16 text-gray-600 dark:text-gray-400">
        <p class="text-lg">No results found for "{{ $query }}".</p>
    </div>
@endif
@endsection

