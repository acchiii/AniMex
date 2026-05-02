@extends('layouts.app')

@section('content')

@push('styles')
<style>
.hero-gradient { background: linear-gradient(to top, #030712 0%, rgba(3,7,18,0.8) 30%, rgba(3,7,18,0.4) 60%, transparent 100%); }
</style>
@endpush

{{-- Hero Section --}}
<div class="relative h-[70vh] overflow-hidden">
    <div class="absolute inset-0" id="hero-bg"></div>
    <div class="absolute inset-0 hero-gradient"></div>
    <div class="relative h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-end pb-20">
        <div class="max-w-2xl" id="hero-content">
            @if($heroAnime->isNotEmpty())
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-white">{{ $heroAnime->first()->title_english ?? $heroAnime->first()->title }}</h1>
                <p class="text-gray-300 mb-6 line-clamp-3">{{ $heroAnime->first()->synopsis }}</p>
                <div class="flex items-center gap-4">
                    <a href="{{ url('/anime/' . $heroAnime->first()->slug) }}" class="hero-watch bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                        Watch Now
                    </a>
                    <a href="{{ url('/anime/' . $heroAnime->first()->slug) }}" class="hero-details bg-gray-800 hover:bg-gray-700 px-6 py-3 rounded-lg font-medium transition">Details</a>
                </div>
            @endif
        </div>
    </div>
    @if($heroAnime->count() > 1)
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2" id="hero-dots">
        @foreach($heroAnime as $i => $a)
            <button onclick="setHeroSlide({{ $i }})" class="hero-dot w-2 h-2 rounded-full transition {{ $i === 0 ? 'bg-purple-500 w-6' : 'bg-white/40' }}"></button>
        @endforeach
    </div>
    @endif
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">

    @if($trending->isNotEmpty())
    {{-- Trending --}}
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold">🔥 Trending</h2>
            <a href="{{ route('anime.popular') }}" class="text-purple-400 hover:text-purple-300 text-sm">View All →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($trending->take(10) as $anime)
                <a href="{{ url('/anime/' . $anime->slug) }}" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        @if($anime->cover_image)
                            <img src="{{ str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image) }}" alt="{{ $anime->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold">{{ substr($anime->title, 0, 1) }}</div>
                        @endif
                        @if($anime->is_trending)
                            <span class="absolute top-2 left-2 bg-purple-600 text-white text-xs px-2 py-1 rounded font-medium">#{{ $loop->iteration }}</span>
                        @endif
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate">{{ $anime->title_english ?? $anime->title }}</h3>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($topRated->isNotEmpty())
    {{-- Top Rated --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">⭐ Top Rated</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($topRated->take(10) as $anime)
                <a href="{{ url('/anime/' . $anime->slug) }}" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        @if($anime->cover_image)
                            <img src="{{ str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image) }}" alt="{{ $anime->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold">{{ substr($anime->title, 0, 1) }}</div>
                        @endif
                        <span class="absolute top-2 right-2 bg-yellow-500 text-black text-xs px-2 py-1 rounded font-bold">{{ number_format($anime->score, 1) }}</span>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate">{{ $anime->title_english ?? $anime->title }}</h3>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($genreSections)
    {{-- Genre Sections --}}
    @foreach($genreSections as $genreName => $genreAnime)
        @if($genreAnime->isNotEmpty())
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold">{{ $genreName }}</h2>
                <a href="{{ route('anime.genre', $genreName) }}" class="text-purple-400 hover:text-purple-300 text-sm">View All →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($genreAnime as $anime)
                    <a href="{{ url('/anime/' . $anime->slug) }}" class="group">
                        <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                            @if($anime->cover_image)
                                <img src="{{ str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image) }}" alt="{{ $anime->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold">{{ substr($anime->title, 0, 1) }}</div>
                            @endif
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate">{{ $anime->title_english ?? $anime->title }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
        @endif
    @endforeach
    @endif

    @if($upcoming->isNotEmpty())
    {{-- Upcoming --}}
    <section>
        <h2 class="text-2xl font-bold mb-4">📅 Upcoming</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach($upcoming as $anime)
                <a href="{{ url('/anime/' . $anime->slug) }}" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        @if($anime->cover_image)
                            <img src="{{ str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image) }}" alt="{{ $anime->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold">{{ substr($anime->title, 0, 1) }}</div>
                        @endif
                        <span class="absolute top-2 left-2 bg-yellow-600 text-white text-xs px-2 py-1 rounded">Upcoming</span>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate">{{ $anime->title_english ?? $anime->title }}</h3>
                </a>
            @endforeach
        </div>
    </section>
    @endif

</div>

@push('scripts')
<script>
const heroData = @json($heroJson);
let currentSlide = 0;
let heroInterval;

function setHeroSlide(index) {
    currentSlide = index;
    const item = heroData[index];
    const bg = document.getElementById('hero-bg');
    const content = document.getElementById('hero-content');
    const dots = document.querySelectorAll('.hero-dot');

    if (bg) {
        const imgUrl = item.cover_image?.startsWith('http') ? item.cover_image : `/storage/${item.cover_image}`;
        bg.style.transition = 'opacity 0.8s';
        bg.style.opacity = '0';
        setTimeout(() => {
            bg.style.backgroundImage = `url(${imgUrl})`;
            bg.style.backgroundSize = 'cover';
            bg.style.backgroundPosition = 'center';
            bg.style.opacity = '1';
        }, 400);
    }

    if (content) {
        content.style.transition = 'opacity 0.8s';
        content.style.opacity = '0';
        setTimeout(() => {
            const watchBtn = content.querySelector('.hero-watch');
            const detailsBtn = content.querySelector('.hero-details');
            content.querySelector('h1').textContent = item.title;
            content.querySelector('p').textContent = item.synopsis;
            if (watchBtn) watchBtn.href = `/anime/${item.slug}`;
            if (detailsBtn) detailsBtn.href = `/anime/${item.slug}`;
            content.style.opacity = '1';
        }, 400);
    }

    dots.forEach((dot, i) => {
        dot.className = i === index ? 'hero-dot w-2 h-2 rounded-full transition bg-purple-500 w-6' : 'hero-dot w-2 h-2 rounded-full transition bg-white/40';
    });

    resetInterval();
}

function nextSlide() {
    setHeroSlide((currentSlide + 1) % heroData.length);
}

function resetInterval() {
    clearInterval(heroInterval);
    heroInterval = setInterval(nextSlide, 5000);
}

if (heroData.length > 0) {
    const bg = document.getElementById('hero-bg');
    const first = heroData[0];
    const imgUrl = first.cover_image?.startsWith('http') ? first.cover_image : `/storage/${first.cover_image}`;
    if (bg) {
        bg.style.backgroundImage = `url(${imgUrl})`;
        bg.style.backgroundSize = 'cover';
        bg.style.backgroundPosition = 'center';
    }
    resetInterval();
}
</script>
@endpush

@endsection
