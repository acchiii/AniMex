<footer class="bg-white dark:bg-[#0a0a0f] border-t border-gray-200 dark:border-gray-800/60 py-8 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-500 dark:text-gray-500">&copy; {{ date('Y') }} AniMex. All rights reserved.</p>
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-gray-500 hover:text-purple-600 dark:hover:text-purple-400 transition">Terms</a>
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-gray-500 hover:text-purple-600 dark:hover:text-purple-400 transition">Privacy</a>
                <a href="mailto:contact@animex.com" class="text-sm text-gray-500 dark:text-gray-500 hover:text-purple-600 dark:hover:text-purple-400 transition">Contact</a>
            </div>
        </div>
    </div>
</footer>
