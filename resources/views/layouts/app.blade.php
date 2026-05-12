<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AniMex') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    @if(config('ads.propellerads.interstitial_zone'))
    <script type="text/javascript" src="//{{ config('ads.propellerads.interstitial_zone') }}.propellerads.com/script.js" async></script>
    @endif
    @if(config('ads.propellerads.popunder_zone'))
    <script type="text/javascript" src="//{{ config('ads.propellerads.popunder_zone') }}.propellerads.com/script.js" async></script>
    @endif
    @stack('scripts')
</body>
</html>
