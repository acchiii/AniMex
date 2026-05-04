@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">{{ $studio->name }}</h1>

    @if($anime->isEmpty())
        <div class="text-center py-16">
            <p class="text-gray-500 dark:text-gray-400 text-lg">No anime found for this studio.</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($anime as $item)
                <a href="{{ url('/anime/' . $item->slug) }}" class="group">
                    <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                        @if($item->cover_image)
                            <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image) }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" loading="lazy" onerror="this.style.display='none'">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold">{{ substr($item->title, 0, 1) }}</div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                            <h3 class="text-sm font-medium text-white truncate">{{ $item->title_english ?? $item->title }}</h3>
                            @if($item->score)
                                <p class="text-xs text-amber-400 mt-0.5 font-medium">★ {{ number_format($item->score, 1) }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $anime->links() }}
        </div>
    @endif
</div>
@endsection
