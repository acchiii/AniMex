@extends('layouts.app')

@section('title', 'Watch Premium Anime Online')
@section('description', 'Stream the best anime in HD quality. New episodes daily. Watch free or go premium for ad-free experience.')

@section('content')
<div class="relative">

{{-- ─── Hero Carousel ─────────────────────────────────────────────────────── --}}
@if($heroAnime->isNotEmpty())
<section class="relative overflow-hidden" style="height: min(85vh, 700px)">
    <div class="swiper hero-swiper h-full">
        <div class="swiper-wrapper">
            @foreach($heroAnime as $hero)
            <div class="swiper-slide relative h-full overflow-hidden">
                {{-- Background --}}
                <div class="absolute inset-0">
                    <img src="{{ $hero->banner_url }}" alt="{{ $hero->display_title }}"
                         class="w-full h-full object-cover"
                         loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                    <div class="absolute inset-0 bg-gradient-to-r from-dark-950 via-dark-950/70 to-transparent"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-dark-950 via-transparent to-dark-950/30"></div>
                </div>

                {{-- Content --}}
                <div class="relative z-10 h-full flex items-center">
                    <div class="max-w-screen-2xl mx-auto px-4 sm:px-8 w-full">
                        <div class="max-w-2xl">
                            {{-- Status + Year --}}
                            <div class="flex items-center gap-3 mb-4 animate-fade-up" style="animation-delay: 0.1s">
                                <span class="badge badge-{{ $hero->status }}">
                                    {{ ucfirst($hero->status) }}
                                </span>
                                @if($hero->year)
                                    <span class="text-gray-400 text-sm">{{ $hero->year }}</span>
                                @endif
                                @if($hero->score > 0)
                                    <span class="score-badge">★ {{ $hero->score_formatted }}</span>
                                @endif
                            </div>

                            {{-- Title --}}
                            <h1 class="font-display text-4xl md:text-6xl font-extrabold text-white leading-tight mb-4 animate-fade-up"
                                style="animation-delay: 0.15s; text-shadow: 0 2px 20px rgba(0,0,0,0.5)">
                                {{ $hero->display_title }}
                            </h1>

                            {{-- Genres --}}
                            <div class="flex flex-wrap gap-2 mb-4 animate-fade-up" style="animation-delay: 0.2s">
                                @foreach($hero->genres->take(4) as $genre)
                                    <a href="/anime?genre={{ $genre->slug }}"
                                       class="px-3 py-1 rounded-full text-xs font-semibold border"
                                       style="background: {{ $genre->color }}20; color: {{ $genre->color }}; border-color: {{ $genre->color }}40">
                                        {{ $genre->name }}
                                    </a>
                                @endforeach
                            </div>

                            {{-- Synopsis --}}
                            <p class="text-gray-300 text-base leading-relaxed mb-8 line-clamp-3 animate-fade-up max-w-lg"
                               style="animation-delay: 0.25s">
                                {{ $hero->synopsis }}
                            </p>

                            {{-- CTA --}}
                            <div class="flex items-center gap-4 animate-fade-up" style="animation-delay: 0.3s">
                                <a href="/anime/{{ $hero->slug }}" class="btn btn-primary btn-lg">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    Watch Now
                                </a>
                                <a href="/anime/{{ $hero->slug }}" class="btn btn-secondary btn-lg glass">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    More Info
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="swiper-pagination absolute bottom-6 left-8 z-20"></div>
    </div>

    {{-- Hero Ad Banner (top right corner) --}}
    @if(isset($ads['homepage_hero_banner']))
        <div class="absolute top-4 right-4 z-20 hidden lg:block ad-slot">
            @include('ads.slot', ['ad' => $ads['homepage_hero_banner']])
        </div>
    @endif
</section>
@endif

{{-- ─── Main Content ──────────────────────────────────────────────────────── --}}
<div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Continue Watching --}}
    @if($continueWatching && $continueWatching->isNotEmpty())
    <section class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">Continue Watching</h2>
            <a href="/history" class="btn btn-ghost btn-sm text-primary-400">View All</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($continueWatching as $history)
            <a href="/anime/{{ $history->anime->slug }}/episode/{{ $history->episode->number }}"
               class="group relative block rounded-2xl overflow-hidden border border-dark-600/50 hover:border-primary-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="relative aspect-card overflow-hidden">
                    <img src="{{ $history->episode->thumbnail_url }}" alt="Episode"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-dark-950/90 to-transparent"></div>

                    {{-- Play overlay --}}
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <div class="w-14 h-14 rounded-full bg-primary-600/90 flex items-center justify-center backdrop-blur-sm">
                            <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>

                    {{-- Episode number --}}
                    <div class="absolute top-2 right-2 px-2 py-1 rounded-lg bg-dark-900/90 text-xs font-semibold text-gray-300">
                        EP {{ $history->episode->number }}
                    </div>

                    {{-- Progress --}}
                    <div class="absolute bottom-0 left-0 right-0">
                        <div class="progress-bar rounded-none">
                            <div class="progress-bar-fill" style="width: {{ $history->progress_percent }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-dark-800">
                    <p class="text-sm font-semibold text-white truncate group-hover:text-primary-400 transition-colors">
                        {{ $history->anime->display_title }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $history->progress_percent }}% watched</p>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Homepage Ad Banner --}}
    @if(isset($ads['homepage_in_feed']))
    <div class="mt-8 ad-slot">
        @include('ads.slot', ['ad' => $ads['homepage_in_feed']])
    </div>
    @endif

    {{-- Trending Anime --}}
    @if($trending->isNotEmpty())
    <section class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">🔥 Trending Now</h2>
            <a href="/anime?sort=popular" class="btn btn-ghost btn-sm text-primary-400">See All</a>
        </div>
        <div class="swiper trending-swiper overflow-visible" style="padding-bottom: 20px">
            <div class="swiper-wrapper">
                @foreach($trending as $anime)
                <div class="swiper-slide">
                    @include('components.anime-card', ['anime' => $anime])
                </div>
                @endforeach
            </div>
            <div class="swiper-button-next trending-next"></div>
            <div class="swiper-button-prev trending-prev"></div>
        </div>
    </section>
    @endif

    {{-- New Episodes --}}
    @if($newEpisodes->isNotEmpty())
    <section class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">🆕 Latest Episodes</h2>
            <a href="/schedule" class="btn btn-ghost btn-sm text-primary-400">Schedule</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($newEpisodes->take(8) as $episode)
            @include('components.episode-card', ['episode' => $episode])
            @endforeach
        </div>
    </section>
    @endif

    {{-- Top Rated --}}
    @if($topRated->isNotEmpty())
    <section class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">⭐ Top Rated</h2>
            <a href="/anime?sort=score" class="btn btn-ghost btn-sm text-primary-400">See All</a>
        </div>
        <div class="swiper top-rated-swiper overflow-visible" style="padding-bottom: 20px">
            <div class="swiper-wrapper">
                @foreach($topRated as $i => $anime)
                <div class="swiper-slide">
                    <div class="relative">
                        {{-- Rank number --}}
                        <div class="absolute -top-3 -left-2 z-10 w-9 h-9 rounded-xl flex items-center justify-center
                                    font-display font-black text-lg
                                    {{ $i < 3 ? 'bg-gradient-to-br from-yellow-400 to-orange-500 text-dark-950' : 'bg-dark-700 text-gray-300 border border-dark-500' }}">
                            {{ $i + 1 }}
                        </div>
                        @include('components.anime-card', ['anime' => $anime])
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Genre Sections --}}
    @foreach($genreSections as $genreName => $genreAnime)
    @if($genreAnime->isNotEmpty())
    <section class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">{{ $genreName }}</h2>
            <a href="/anime?genre={{ strtolower($genreName) }}" class="btn btn-ghost btn-sm text-primary-400">See All</a>
        </div>
        <div class="swiper genre-swiper overflow-visible" style="padding-bottom: 20px" data-genre="{{ $genreName }}">
            <div class="swiper-wrapper">
                @foreach($genreAnime as $anime)
                <div class="swiper-slide">
                    @include('components.anime-card', ['anime' => $anime])
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    @endforeach

    {{-- Premium Banner --}}
    @guest
    <section class="mt-16 mb-8">
        <div class="relative overflow-hidden rounded-3xl p-8 md:p-12"
             style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 30%, #4338CA 60%, #6366F1 100%)">
            {{-- Decorative circles --}}
            <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-white/5"></div>
            <div class="absolute -bottom-8 -left-8 w-48 h-48 rounded-full bg-white/5"></div>
            <div class="absolute top-1/2 right-1/4 w-24 h-24 rounded-full bg-white/5"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-yellow-400 text-2xl">⭐</span>
                        <span class="font-display font-bold text-yellow-400 text-sm uppercase tracking-widest">Go Premium</span>
                    </div>
                    <h2 class="font-display text-3xl md:text-4xl font-extrabold text-white mb-3">
                        Ad-Free Anime Streaming
                    </h2>
                    <p class="text-indigo-200 text-lg max-w-md">
                        Enjoy unlimited anime in 1080p HD, no ads, offline downloads, and exclusive content.
                    </p>
                    <div class="flex flex-wrap gap-4 mt-4 text-sm text-indigo-200">
                        <span>✓ No advertisements</span>
                        <span>✓ 1080p HD quality</span>
                        <span>✓ Offline downloads</span>
                        <span>✓ Early access</span>
                    </div>
                </div>
                <div class="flex flex-col items-center gap-4 shrink-0">
                    <div class="text-center">
                        <span class="text-5xl font-display font-black text-white">$4</span>
                        <span class="text-indigo-300 text-lg">/month</span>
                    </div>
                    <a href="/premium" class="btn btn-xl bg-white text-primary-700 hover:bg-gray-100 shadow-xl font-bold">
                        Start Free Trial
                    </a>
                    <p class="text-xs text-indigo-300">Cancel anytime • No credit card required</p>
                </div>
            </div>
        </div>
    </section>
    @endguest

</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero swiper
    new Swiper('.hero-swiper', {
        modules: [Swiper.Navigation, Swiper.Pagination, Swiper.Autoplay, Swiper.EffectFade],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        autoplay: { delay: 6000, disableOnInteraction: false },
        pagination: { el: '.swiper-pagination', clickable: true },
        loop: true,
        speed: 800,
    });

    // Trending swiper
    const swiperConfig = {
        spaceBetween: 16,
        slidesPerView: 2.3,
        breakpoints: {
            480:  { slidesPerView: 3.2 },
            768:  { slidesPerView: 4.2 },
            1024: { slidesPerView: 5.2, navigation: { nextEl: '.trending-next', prevEl: '.trending-prev' } },
            1280: { slidesPerView: 6.2 },
            1536: { slidesPerView: 7.2 },
        },
        freeMode: true,
    };

    new Swiper('.trending-swiper',    { ...swiperConfig });
    new Swiper('.top-rated-swiper',   { ...swiperConfig });
    document.querySelectorAll('.genre-swiper').forEach(el => new Swiper(el, { ...swiperConfig }));
});
</script>
@endpush