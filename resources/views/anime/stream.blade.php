@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Video Player -->
    <div class="aspect-video bg-black rounded-lg overflow-hidden mb-4">
        @if($episode->sources->count())
            <video id="video-player" class="w-full h-full" controls preload="metadata">
                <source src="{{ $episode->sources->first()->url }}" type="{{ $episode->sources->first()->mime_type ?? 'video/mp4' }}">
                @foreach($episode->subtitles as $subtitle)
                    <track label="{{ $subtitle->label }}" kind="subtitles" srclang="{{ $subtitle->language }}" src="{{ $subtitle->url }}">
                @endforeach
                Your browser does not support the video tag.
            </video>
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <p>No video sources available</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Episode Info -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $anime->title_english ?: $anime->title }}</h1>
            <p class="text-gray-600 dark:text-gray-400">Episode {{ $episode->number }}: {{ $episode->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if($prevEpisode)
                <a href="{{ route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $prevEpisode->number]) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded-lg transition text-gray-900 dark:text-white">
                    ← Previous
                </a>
            @endif
            @if($nextEpisode)
                <a href="{{ route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $nextEpisode->number]) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded-lg transition text-gray-900 dark:text-white">
                    Next →
                </a>
            @endif
        </div>
    </div>

    <!-- Source Selection -->
    @if($episode->sources->count() > 1)
        <div class="mb-4">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Video Source</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($episode->sources as $index => $source)
                    <button onclick="changeSource('{{ $source->url }}', '{{ $source->mime_type ?? 'video/mp4' }}')" 
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 {{ $index === 0 ? 'ring-2 ring-purple-500' : '' }}">
                        {{ $source->quality }} - {{ $source->server }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Subtitle Selection -->
    @if($episode->subtitles->count() > 0)
        <div class="mb-4">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subtitles</h3>
            <div class="flex flex-wrap gap-2">
                <button onclick="disableSubtitles()" class="px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300">Off</button>
                @foreach($episode->subtitles as $subtitle)
                    <button onclick="enableSubtitle('{{ $subtitle->language }}')" class="px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300">
                        {{ $subtitle->label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Back to Anime -->
    <div class="mt-4">
        <a href="{{ route('anime.show', $anime->slug) }}" class="text-purple-600 dark:text-purple-500 hover:text-purple-700 dark:hover:text-purple-400">
            ← Back to {{ $anime->title_english ?: $anime->title }}
        </a>
    </div>
</div>

@push('scripts')
<script>
const player = document.getElementById('video-player');

function changeSource(url, mimeType) {
    if (player) {
        player.src = url;
        player.load();
        player.play();
    }
}

function disableSubtitles() {
    if (player) {
        for (let track of player.textTracks) {
            track.mode = 'disabled';
        }
    }
}

function enableSubtitle(language) {
    if (player) {
        for (let track of player.textTracks) {
            track.mode = (track.language === language) ? 'showing' : 'hidden';
        }
    }
}

// Save progress every 10 seconds
if (player) {
    player.addEventListener('timeupdate', function() {
        if (Math.floor(player.currentTime) % 10 === 0) {
            saveProgress(Math.floor(player.currentTime), Math.floor(player.duration));
        }
    });
}

function saveProgress(progress, duration) {
    fetch('{{ route("home") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ 
            episode_id: {{ $episode->id }},
            progress: progress,
            duration: duration 
        })
    });
}
</script>
@endpush
@endsection
