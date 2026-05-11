@extends('layouts.app')

@section('content')
@php
    $coverUrl = $anime->cover_image 
        ? (str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image))
        : asset('images/placeholder-cover.jpg');
    $bannerUrl = $anime->banner_image 
        ? (str_starts_with($anime->banner_image, 'http') ? $anime->banner_image : asset('storage/' . $anime->banner_image))
        : $coverUrl;
@endphp

<div class="relative">
    <!-- Banner Background -->
    <div class="absolute inset-0 h-[50vh] overflow-hidden">
        <img src="{{ $bannerUrl }}" alt="{{ $anime->title }}" onerror="this.style.display='none'" class="w-full h-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-50 dark:from-gray-950 via-gray-50/80 dark:via-gray-950/80 to-transparent"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Cover Image -->
            <div class="flex-shrink-0 w-full md:w-64">
                <img src="{{ $coverUrl }}" alt="{{ $anime->title }}" onerror="this.style.display='none'" class="w-full rounded-lg shadow-xl">
            </div>

            <!-- Anime Info -->
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    @if($anime->status === 'ongoing')
                        <span class="bg-green-600 text-white text-xs px-2 py-1 rounded">Ongoing</span>
                    @elseif($anime->status === 'completed')
                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded">Completed</span>
                    @endif
                    <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $anime->type }}</span>
                    @if($anime->episodes_count)
                        <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $anime->episodes_count }} episodes</span>
                    @endif
                </div>

                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $anime->title_english ?: $anime->title }}</h1>
                @if($anime->title_english)
                    <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $anime->title_japanese }}</p>
                @endif

                <!-- Rating & Actions -->
                <div class="flex items-center gap-4 mb-4">
                    @if($anime->score)
                        <div class="flex items-center gap-1">
                            <span class="text-yellow-500">★</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ number_format($anime->score, 1) }}</span>
                        </div>
                    @endif

                    @auth
                        <button onclick="toggleFavorite({{ $anime->id }})" 
                            class="flex items-center gap-1 px-3 py-1 rounded {{ $isFavorite ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }} hover:bg-opacity-80 transition">
                            <span>{{ $isFavorite ? '♥' : '♡' }}</span>
                            <span class="text-sm">{{ $isFavorite ? 'Favorited' : 'Favorite' }}</span>
                        </button>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-1" id="rating-section">
                            @for($i = 1; $i <= 10; $i++)
                                <button onclick="rateAnime({{ $anime->id }}, {{ $i }})" 
                                    class="text-{{ $userRating && $userRating->score >= $i ? 'yellow' : 'gray' }}-500 hover:text-yellow-400 transition">
                                    ★
                                </button>
                            @endfor
                            @if($userRating)
                                <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">Your rating: {{ $userRating->score }}</span>
                            @endif
                        </div>
                    @endauth
                </div>

                <!-- Genres -->
                @if($anime->genres->count())
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($anime->genres as $genre)
                            <a href="{{ route('anime.genre', $genre->slug) }}" 
                                class="bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 px-3 py-1 rounded text-sm text-gray-700 dark:text-gray-300 dark:hover:text-white transition">
                                {{ $genre->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <!-- Synopsis -->
                <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $anime->synopsis }}</p>

                <!-- Studio -->
                @if($anime->studio)
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Studio: <a href="{{ route('anime.studio', $anime->studio->slug) }}" class="text-purple-600 dark:text-purple-500 hover:text-purple-700 dark:hover:text-purple-400">{{ $anime->studio->name }}</a></p>
                @endif
            </div>
        </div>

        <!-- Episodes List -->
        @if($anime->episodes->count())
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Episodes</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    @foreach($anime->episodes as $episode)
                        <a href="{{ route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $episode->number]) }}" 
                            class="flex flex-col items-center gap-1 p-2 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition text-center @if($episode->is_filler) ring-1 ring-yellow-600 @endif">
                            <span class="text-xs font-medium text-gray-900 dark:text-white leading-tight truncate w-full">{{ $episode->title ?: 'Episode ' . $episode->number }}</span>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400">EP {{ $episode->number }}</span>
                            @if($episode->sources->count())
                                <span class="text-[10px] text-green-600 dark:text-green-400">{{ $episode->sources->count() }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Related Anime -->
        @if($related->count())
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Related Anime</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($related as $item)
                        <a href="{{ route('anime.show', $item->slug) }}" class="group">
                            <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
                                @if($item->cover_image)
                                    <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image) }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.style.display='none'">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                @endif
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-gray-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 truncate">{{ $item->title }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@auth
@push('scripts')
<script>
function toggleFavorite(animeId) {
    fetch(`/anime/${animeId}/favorite`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.favorited) {
            window.location.reload();
        } else {
            window.location.reload();
        }
    });
}

function rateAnime(animeId, score) {
    fetch(`/anime/${animeId}/rate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ score })
    })
    .then(res => res.json())
    .then(data => {
        window.location.reload();
    });
}
</script>
@endpush
@endauth
@endsection
