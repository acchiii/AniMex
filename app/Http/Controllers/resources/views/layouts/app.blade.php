<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-cloak>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#070B14">

    {{-- SEO --}}
    <title>@yield('title', config('app.name')) — AniMex</title>
    <meta name="description" content="@yield('description', 'AniMex — Stream the best anime online in HD. Free & Premium plans available.')">
    <meta name="keywords"    content="@yield('keywords', 'anime, streaming, watch anime online, anime episodes')">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', 'Stream premium anime on AniMex')">
    <meta property="og:image"       content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:url"         content="{{ url()->current() }}">
    <meta name="twitter:card"       content="summary_large_image">

    {{-- Theme: prevent FOUC --}}
    <script>
        (function() {
            const t = localStorage.getItem('animex_theme')?.replace(/"/g,'') || 'system';
            const dark = t === 'dark' || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.add(dark ? 'dark' : 'light');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Plyr CSS --}}
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">

    {{-- Extra head content --}}
    @stack('head')

    {{-- Structured data --}}
    @yield('structured_data')
</head>
<body class="min-h-screen flex flex-col" x-data="{
    sidebarOpen: false,
    '$store': $store,
}" x-init="$store.theme.init()">

    {{-- Toast container --}}
    <div
        x-data="toastManager()"
        x-on:toast.window="add($event.detail.message, $event.detail.type)"
        class="fixed top-4 right-4 z-[200] flex flex-col gap-2 pointer-events-none"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-card
                       backdrop-blur-md border max-w-sm"
                :class="{
                    'bg-green-500/20 border-green-500/30 text-green-300': toast.type === 'success',
                    'bg-red-500/20 border-red-500/30 text-red-300':       toast.type === 'error',
                    'bg-primary-500/20 border-primary-500/30 text-primary-300': toast.type === 'info',
                }"
            >
                <svg x-show="toast.type==='success'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="toast.type==='error'"   class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <svg x-show="toast.type==='info'"    class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-medium" x-text="toast.message"></p>
                <button @click="remove(toast.id)" class="ml-auto text-current/60 hover:text-current transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Search Modal --}}
    <div x-data="searchModal()" @keydown.window.prevent.ctrl.k="openModal()" id="search-modal-wrapper">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[100] flex items-start justify-center pt-20 px-4"
            style="background: rgba(3,4,10,0.85); backdrop-filter: blur(8px);"
            @click.self="close()"
        >
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-2xl bg-dark-800 border border-dark-500 rounded-2xl shadow-2xl overflow-hidden"
            >
                {{-- Search input --}}
                <div class="flex items-center gap-3 px-5 py-4 border-b border-dark-600">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        x-ref="input"
                        x-model="query"
                        type="text"
                        placeholder="Search anime, genres, studios..."
                        class="flex-1 bg-transparent text-white placeholder-gray-500 focus:outline-none text-lg"
                        @keydown.escape="close()"
                    >
                    <div x-show="loading" class="w-5 h-5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin shrink-0"></div>
                    <kbd class="hidden sm:inline-flex items-center px-2 py-1 rounded bg-dark-600 text-xs text-gray-400 border border-dark-400">ESC</kbd>
                </div>

                {{-- Results --}}
                <div x-show="results.length > 0" class="max-h-96 overflow-y-auto">
                    <div class="p-2">
                        <template x-for="anime in results" :key="anime.id">
                            <a :href="`/anime/${anime.slug}`" @click="close()"
                               class="flex items-center gap-4 p-3 rounded-xl hover:bg-dark-700 transition-colors duration-150 group">
                                <img :src="anime.cover" :alt="anime.title"
                                     class="w-12 h-16 object-cover rounded-lg border border-dark-500">
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-semibold group-hover:text-primary-400 transition-colors truncate" x-text="anime.title"></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-400" x-text="anime.type"></span>
                                        <span class="text-gray-600">·</span>
                                        <span class="text-xs text-gray-400" x-text="anime.year"></span>
                                        <span x-show="anime.score > 0" class="score-badge text-[10px] px-1.5 py-0.5 ml-auto">
                                            ★ <span x-text="anime.score"></span>
                                        </span>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-600 group-hover:text-primary-400 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Empty state --}}
                <div x-show="query.length >= 2 && !loading && results.length === 0" class="px-5 py-10 text-center">
                    <p class="text-gray-400">No results for "<span x-text="query" class="text-white"></span>"</p>
                    <a :href="`/search?q=${query}`" class="mt-3 btn btn-secondary btn-sm mx-auto inline-flex">Advanced Search</a>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-dark-600 flex items-center justify-between text-xs text-gray-500">
                    <span>Search across all titles</span>
                    <div class="flex items-center gap-3">
                        <span><kbd class="px-1.5 py-0.5 bg-dark-600 rounded border border-dark-400">↑↓</kbd> navigate</span>
                        <span><kbd class="px-1.5 py-0.5 bg-dark-600 rounded border border-dark-400">↵</kbd> open</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Navigation --}}
    <header class="fixed top-0 left-0 right-0 z-50 h-16 glass border-b border-white/5">
        <div class="max-w-screen-2xl mx-auto px-4 h-full flex items-center gap-4">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2 shrink-0 mr-4">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-500 to-neon-purple flex items-center justify-center">
                    <span class="text-white font-display font-black text-sm">A</span>
                </div>
                <span class="font-display font-bold text-xl text-white hidden sm:block">
                    Ani<span class="text-gradient">Mex</span>
                </span>
            </a>

            {{-- Desktop Nav Links --}}
            <nav class="hidden lg:flex items-center gap-1">
                <a href="/"          class="nav-link {{ request()->is('/') ? 'active' : '' }}">Home</a>
                <a href="/anime"     class="nav-link {{ request()->is('anime') ? 'active' : '' }}">Anime</a>
                <a href="/schedule"  class="nav-link">Schedule</a>
                <a href="/movies"    class="nav-link">Movies</a>
                <a href="/genres"    class="nav-link">Genres</a>
            </nav>

            {{-- Spacer --}}
            <div class="flex-1"></div>

            {{-- Search button --}}
            <button
                x-data
                @click="$dispatch('open-search')"
                onclick="document.getElementById('search-modal-wrapper').__x.$data.openModal()"
                class="flex items-center gap-2 px-3 py-2 rounded-xl bg-dark-700 border border-dark-500
                       text-gray-400 hover:text-white hover:border-primary-500/50 transition-all duration-200
                       text-sm"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="hidden sm:block">Search...</span>
                <kbd class="hidden md:inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-dark-600 border border-dark-400">⌘K</kbd>
            </button>

            {{-- Theme toggle --}}
            <button
                x-data
                @click="$store.theme.toggle()"
                class="p-2.5 rounded-xl hover:bg-white/10 text-gray-400 hover:text-white transition-all duration-200"
            >
                <svg x-show="$store.theme.isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg x-show="!$store.theme.isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>

            {{-- User menu / Auth --}}
            @auth
                {{-- Notifications --}}
                <div x-data="notifications()" class="relative">
                    <button @click="open = !open; if(open) fetchNotifications()"
                            class="relative p-2.5 rounded-xl hover:bg-white/10 text-gray-400 hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="unreadCount > 0" class="notification-dot animate-pulse"></span>
                    </button>
                    {{-- Notification dropdown --}}
                    <div x-show="open" @click.outside="open=false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="dropdown-menu w-80 right-0 max-h-96 overflow-y-auto">
                        <div class="p-4 border-b border-dark-600 flex items-center justify-between">
                            <span class="font-semibold text-white">Notifications</span>
                            <button @click="markAllRead()" class="text-xs text-primary-400 hover:text-primary-300">Mark all read</button>
                        </div>
                        <div x-show="items.length === 0" class="p-8 text-center text-gray-500 text-sm">No notifications</div>
                        <template x-for="n in items" :key="n.id">
                            <div :class="n.read_at ? 'opacity-60' : ''" class="dropdown-item cursor-default">
                                <div class="w-2 h-2 rounded-full bg-primary-500 shrink-0" x-show="!n.read_at"></div>
                                <div>
                                    <p class="text-sm text-white" x-text="n.data?.message || 'Notification'"></p>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="n.created_at_human"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- User Avatar --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2.5">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                             class="w-9 h-9 rounded-xl object-cover border-2 border-dark-500 hover:border-primary-500 transition-colors">
                        <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="dropdown-menu right-0 w-56">
                        <div class="px-4 py-3 border-b border-dark-600">
                            <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
                            @if(auth()->user()->isPremium())
                                <span class="mt-1 badge bg-yellow-500/20 text-yellow-400 border-yellow-500/30 text-[10px]">⭐ PREMIUM</span>
                            @endif
                        </div>
                        <a href="/profile"   class="dropdown-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Profile
                        </a>
                        <a href="/watchlist" class="dropdown-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            My Watchlist
                        </a>
                        <a href="/history" class="dropdown-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Watch History
                        </a>
                        <a href="/downloads" class="dropdown-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Downloads
                        </a>
                        @if(auth()->user()->isAdmin())
                        <div class="border-t border-dark-600 my-1"></div>
                        <a href="/admin" class="dropdown-item text-primary-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Admin Panel
                        </a>
                        @endif
                        <div class="border-t border-dark-600 my-1"></div>
                        <form method="POST" action="/logout">
                            @csrf
                            <button type="submit" class="dropdown-item w-full text-red-400 hover:text-red-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="/login"    class="btn btn-ghost btn-sm">Sign In</a>
                <a href="/register" class="btn btn-primary btn-sm">Join Free</a>
            @endauth

            {{-- Mobile menu --}}
            <button @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-2.5 rounded-xl hover:bg-white/10 text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </header>

    {{-- Mobile Sidebar --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-40 lg:hidden"
         style="background: rgba(0,0,0,0.7)"
         @click="sidebarOpen = false">
    </div>
    <aside x-show="sidebarOpen"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed top-0 left-0 h-full w-72 z-50 lg:hidden bg-dark-900 border-r border-dark-600 pt-16 overflow-y-auto">
        <nav class="p-4 flex flex-col gap-1">
            <a href="/"         class="nav-link">🏠 Home</a>
            <a href="/anime"    class="nav-link">📺 Anime</a>
            <a href="/schedule" class="nav-link">📅 Schedule</a>
            <a href="/movies"   class="nav-link">🎬 Movies</a>
            <a href="/genres"   class="nav-link">🏷️ Genres</a>
            @auth
                <div class="border-t border-dark-600 my-2"></div>
                <a href="/profile"   class="nav-link">👤 Profile</a>
                <a href="/watchlist" class="nav-link">❤️ Watchlist</a>
                <a href="/history"   class="nav-link">🕐 History</a>
            @else
                <div class="border-t border-dark-600 my-2"></div>
                <a href="/login"    class="btn btn-secondary w-full">Sign In</a>
                <a href="/register" class="btn btn-primary w-full mt-2">Join Free</a>
            @endauth
        </nav>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 pt-16">
        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ session("success") }}', type: 'success' }}));
                });
            </script>
        @endif
        @if(session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ session("error") }}', type: 'error' }}));
                });
            </script>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-20 border-t border-dark-600/50 bg-dark-900">
        @include('partials.footer')
    </footer>

    {{-- GDPR Cookie Banner --}}
    @include('partials.cookie-banner')

    {{-- Global JS vars --}}
    <script>
        window._csrf = '{{ csrf_token() }}';
        window._auth = @json(auth()->check());
        @auth window._userId = @json(auth()->id()); @endauth
    </script>

    @stack('scripts')
</body>
</html>