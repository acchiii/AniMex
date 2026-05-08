<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - AniMex Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { scrollbar-width: thin; scrollbar-color: #4b5563 transparent; }
        *::-webkit-scrollbar { width: 6px; }
        *::-webkit-scrollbar-track { background: transparent; }
        *::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 3px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100 font-[Inter] antialiased">
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar (responsive) --}}
            <aside class="w-64 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col flex-shrink-0 hidden md:flex">


            <div class="p-5 border-b border-gray-200 dark:border-gray-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center font-bold text-white">A</div>
                    <span class="text-lg font-semibold">AniMex Admin</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-1 bg-gray-50 dark:bg-gray-900">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    <span class="sr-only">Dashboard</span>
                </a>
                <a href="{{ route('admin.anime.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.anime.*') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-4 3V1m-4 0h8m-8 0H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-3m-4 3h4m-4 0l4 4m4-4l-4 4"/></svg>
                    <span class="sr-only">Anime</span>
                </a>
                <a href="{{ route('admin.import.search') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.import.*') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' }} transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span class="sr-only">Import Anime</span>
                </a>
                <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:text-gray-200 hover:bg-gray-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    View Site
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-sm font-medium">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-red-400 hover:bg-gray-800 transition">Sign Out</button>
                </form>
            </div>
        </aside>

        {{-- Mobile Off-canvas Nav --}}
            <div id="admin-mobile-menu" class="fixed inset-0 z-50 lg:hidden hidden">
            <div class="absolute inset-0 bg-black/60" onclick="toggleAdminMobileMenu(false)" aria-hidden="true"></div>
            <div class="relative bg-white dark:bg-gray-900 w-80 max-w-[85vw] h-full border-r border-gray-200 dark:border-gray-800">
                        <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div class="min-w-0">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center font-bold text-white">A</div>
                            <span class="text-lg font-semibold">AniMex Admin</span>
                        </a>
                    </div>
                    <button type="button" class="p-2 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-gray-200 transition" onclick="toggleAdminMobileMenu(false)" aria-label="Close menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>


                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" onclick="toggleAdminMobileMenu(false)" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-800 dark:text-gray-400' }} transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.anime.index') }}" onclick="toggleAdminMobileMenu(false)" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.anime.*') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' }} transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-4 3V1m-4 0h8m-8 0H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-3m-4 3h4m-4 0l4 4m4-4l-4 4"/></svg>
                        Anime
                    </a>
                    <a href="{{ route('admin.import.search') }}" onclick="toggleAdminMobileMenu(false)" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.import.*') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800' }} transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span class="sr-only">Import Anime</span>
                    </a>
                    <a href="{{ url('/') }}" target="_blank" onclick="toggleAdminMobileMenu(false)" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:text-gray-200 hover:bg-gray-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        View Site
                    </a>
                </nav>

                <div class="p-4 border-t border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-sm font-medium">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">Sign Out</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden w-full">
            {{-- Top Bar --}}
            <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 sm:px-6 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <button type="button" class="md:hidden p-2 rounded-lg text-gray-400 hover:bg-gray-800 hover:text-gray-200 transition" onclick="toggleAdminMobileMenu(true)" aria-label="Open admin menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h1 class="text-xl font-semibold truncate">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <button type="button" onclick="toggleTheme()" class="px-3 py-2 rounded-lg text-sm bg-gray-800/70 text-gray-200 hover:bg-gray-800 transition border border-gray-700/60 dark:bg-gray-800/60 dark:text-gray-100 dark:border-gray-700/60" aria-label="Toggle theme">
                            <svg class="w-[18px] h-[18px] hidden dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="5"/>
                                <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                            </svg>
                            <svg class="w-[18px] h-[18px] block dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </button>
                        @yield('header-actions')
                    </div>
                </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-6 bg-green-600/10 border border-green-600/20 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-6 bg-red-600/10 border border-red-600/20 text-red-400 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Admin login overlay --}}
    @include('layouts.partials.admin-auth-modals')


    <script>
        (function() {
            const stored = localStorage.getItem('theme');
            if (stored) {
                if (stored === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        })();

        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        function toggleAdminMobileMenu(forceOpen) {
            const menu = document.getElementById('admin-mobile-menu');
            if (!menu) return;

            if (forceOpen === true) {
                menu.classList.remove('hidden');
                return;
            }

            if (forceOpen === false) {
                menu.classList.add('hidden');
                return;
            }

            menu.classList.toggle('hidden');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                toggleAdminMobileMenu(false);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
