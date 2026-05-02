@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-white mb-8">📅 Anime Schedule</h1>

    @if(count($anime) === 0)
        <div class="text-center py-12">
            <p class="text-gray-400 text-lg">No ongoing anime to display.</p>
        </div>
    @else
        <div class="space-y-8">
            @foreach($anime as $season => $seasonAnime)
            <section>
                <h2 class="text-xl font-bold text-white mb-4">{{ $season }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach($seasonAnime as $item)
                        <a href="{{ url('/anime/' . $item->slug) }}" class="group">
                            <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                                @if($item->cover_image)
                                    <img src="{{ $item->cover_image }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold">{{ substr($item->title, 0, 1) }}</div>
                                @endif
                                <span class="absolute top-2 left-2 bg-green-600 text-white text-xs px-2 py-1 rounded">Ongoing</span>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate">{{ $item->title_english ?? $item->title }}</h3>
                            @if($item->genres->isNotEmpty())
                                <p class="text-xs text-gray-500 mt-1">{{ $item->genres->pluck('name')->join(', ') }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
