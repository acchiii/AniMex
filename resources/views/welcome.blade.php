clear
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <section class="text-center py-20">
        <h1 class="text-5xl font-bold mb-4 bg-gradient-to-r from-purple-400 to-pink-500 bg-clip-text text-transparent">
            AniMex
        </h1>
        <p class="text-xl text-gray-400 mb-8">Premium Anime Streaming Platform</p>
<div class="flex items-center justify-center gap-4">
            <a href="{{ route('anime.index') }}" class="bg-purple-600 hover:bg-purple-700 px-8 py-3 rounded-lg font-medium transition">Start Watching</a>
            <a href="{{ route('anime.popular') }}" class="bg-gray-800 hover:bg-gray-700 px-8 py-3 rounded-lg font-medium transition">Browse Anime</a>
        </div>
    </section>
</div>
@endsection
