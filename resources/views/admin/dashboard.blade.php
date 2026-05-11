@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('header-actions')

@endsection

@section('content')
    @php
        $locked = isset($locked_admin) && $locked_admin;
        $showOverlay = $locked || (!auth()->check() || !auth()->user()->isAdmin());
    @endphp

    @if($showOverlay)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                openAdminAuthModal?.();
            });
        </script>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Anime</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['total_anime'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Episodes</p>

            <p class="text-3xl font-bold mt-1">{{ $stats['total_episodes'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Users</p>

            <p class="text-3xl font-bold mt-1">{{ $stats['total_users'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Ongoing</p>

            <p class="text-3xl font-bold mt-1 text-green-400 dark:text-green-400">{{ $stats['ongoing_anime'] ?? 0 }}</p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Featured</p>
            <p class="text-3xl font-bold mt-1 text-purple-600 dark:text-purple-400">{{ $stats['featured_anime'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Trending</p>
            <p class="text-3xl font-bold mt-1 text-amber-600 dark:text-amber-400">{{ $stats['trending_anime'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Genres</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['total_genres'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">Studios</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['total_studios'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-medium text-gray-900 dark:text-gray-100">Recently Added Anime</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($recentAnime ?? [] as $item)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <div class="w-10 h-14 rounded bg-gray-100 dark:bg-gray-800 overflow-hidden flex-shrink-0">
                            @if($item->cover_image)
                                <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $item->title_english ?: $item->title }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $item->type }} &middot; {{ $item->status }} @if($item->studio) &middot; {{ $item->studio->name }} @endif</p>
                        </div>
                        <a href="{{ route('admin.anime.edit', $item) }}" class="text-xs text-purple-400 hover:text-purple-300 dark:text-purple-300">Edit</a>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">No anime added yet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-medium text-gray-900 dark:text-gray-100">Recent Episodes</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($recentEpisodes ?? [] as $ep)
                    <div class="px-5 py-3 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center text-sm font-medium flex-shrink-0 text-gray-900 dark:text-gray-100">{{ $ep->number }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $ep->title ?: 'Episode ' . $ep->number }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ optional($ep->anime)->title_english ?: optional($ep->anime)->title }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">No episodes yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

