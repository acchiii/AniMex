@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-white">@if(request('search'))Results for "{{ request('search') }}"@else Browse Anime @endif</h1>
    </div>

    @if($anime->isEmpty())
        <div class="text-center py-12">
            <p class="text-gray-400 text-lg">@if(request('search'))No results found for "{{ request('search') }}".@else No anime found.@endif</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($anime as $item)
                <a href="{{ url('/anime/' . $item->slug) }}" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        @if($item->cover_image)
                            <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image) }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold">{{ substr($item->title, 0, 1) }}</div>
                        @endif
                        @if($item->status === 'ongoing')
                            <span class="absolute top-2 left-2 bg-green-600 text-white text-xs px-2 py-1 rounded">Ongoing</span>
                        @elseif($item->status === 'completed')
                            <span class="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">Completed</span>
                        @endif
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate">{{ $item->title_english ?? $item->title }}</h3>
                    @if($item->score)
                        <p class="text-xs text-yellow-400 mt-1">★ {{ number_format($item->score, 1) }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $anime->links() }}
        </div>
    @endif
</div>
@endsection
