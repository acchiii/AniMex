<header class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-8">
                <a href="/" class="text-2xl font-bold text-purple-500">AniMex</a>
<nav class="hidden md:flex items-center gap-6">
                    <a href="{{ route('home') }}" class="text-gray-300 hover:text-white transition">Home</a>
                    <a href="{{ route('anime.index') }}" class="text-gray-300 hover:text-white transition">Browse</a>
                    <a href="{{ route('anime.popular') }}" class="text-gray-300 hover:text-white transition">Popular</a>
                    <a href="{{ route('anime.schedule') }}" class="text-gray-300 hover:text-white transition">Schedule</a>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:block">
                    <form method="GET" action="{{ route('anime.index') }}">
                        <input type="search" name="search" placeholder="Search anime..." value="{{ request('search') }}" class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </form>
                </div>
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-gray-300 hover:text-white">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-gray-300 hover:text-white">Log in</a>
                    <a href="{{ route('register') }}" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">Sign up</a>
                @endauth
            </div>
        </div>
    </div>
</header>
