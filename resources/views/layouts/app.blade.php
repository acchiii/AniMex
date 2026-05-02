<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AniMex') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95' }
                    }
                }
            }
        };
        (function() {
            const stored = localStorage.getItem('theme');
            if (stored) {
                if (stored === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        * { scrollbar-width: none; }
        *::-webkit-scrollbar { display: none; }
        html { scrollbar-width: none; }
        html::-webkit-scrollbar { display: none; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-[#0a0a0f] dark:text-gray-100 antialiased">
    <div id="app">
        @include('layouts.partials.header')
        <main class="min-h-screen">
            @yield('content')
        </main>
        @include('layouts.partials.footer')
        @include('layouts.partials.auth-modals')
    </div>
    @stack('scripts')
</body>
</html>
