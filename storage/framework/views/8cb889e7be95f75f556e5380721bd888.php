<header class="bg-white/80 dark:bg-[#0a0a0f]/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-800/60 sticky top-0 z-50 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-4">
            
            <a href="/" class="text-xl font-bold bg-gradient-to-r from-purple-500 to-violet-400 bg-clip-text text-transparent shrink-0">AniMex</a>

            
            <nav class="hidden lg:flex items-center gap-6 flex-1 justify-center">
                <a href="<?php echo e(route('home')); ?>" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition <?php echo e(request()->routeIs('home') ? 'text-purple-600 dark:text-purple-400' : ''); ?>">Home</a>
                <a href="<?php echo e(route('anime.index')); ?>" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition <?php echo e(request()->routeIs('anime.index') ? 'text-purple-600 dark:text-purple-400' : ''); ?>">Browse</a>
                <a href="<?php echo e(route('anime.popular')); ?>" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition <?php echo e(request()->routeIs('anime.popular') ? 'text-purple-600 dark:text-purple-400' : ''); ?>">Popular</a>
                <a href="<?php echo e(route('anime.schedule')); ?>" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition <?php echo e(request()->routeIs('anime.schedule') ? 'text-purple-600 dark:text-purple-400' : ''); ?>">Schedule</a>
            </nav>

            
            <div class="flex items-center gap-3 shrink-0">
                <form method="GET" action="<?php echo e(route('anime.index')); ?>" class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="search" name="search" placeholder="Search..." value="<?php echo e(request('search')); ?>" class="w-36 sm:w-48 lg:w-56 bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-gray-700/50 rounded-full pl-9 pr-4 py-1.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                </form>

                <?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(url('/dashboard')); ?>" class="hidden lg:block text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition">Dashboard</a>
                <?php else: ?>
                    <button onclick="openAuthModal('login')" class="hidden lg:block text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition">Log in</button>
                    <button onclick="openAuthModal('register')" class="hidden lg:block bg-purple-600 hover:bg-purple-700 px-3 py-1.5 rounded-full text-sm font-medium text-white transition shadow-sm shadow-purple-600/20">Sign up</button>
                <?php endif; ?>

                <button onclick="toggleTheme()" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 transition">
                    <svg class="w-[18px] h-[18px] hidden dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <svg class="w-[18px] h-[18px] block dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 transition" id="mobile-menu-btn">
                    <svg class="w-[18px] h-[18px]" id="menu-icon-open" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg class="w-[18px] h-[18px] hidden" id="menu-icon-close" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>

    
    <div id="mobile-menu" class="hidden fixed inset-0 top-16 lg:hidden z-50">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="toggleMobileMenu()"></div>
        <nav class="relative bg-white dark:bg-[#0a0a0f] border-t border-gray-200 dark:border-gray-800/60 px-4 py-5 space-y-1 max-h-[calc(100vh-4rem)] overflow-y-auto">
            <a href="<?php echo e(route('home')); ?>" onclick="toggleMobileMenu()" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 <?php echo e(request()->routeIs('home') ? 'bg-gray-100 dark:bg-white/5 text-purple-600 dark:text-purple-400' : ''); ?>">Home</a>
            <a href="<?php echo e(route('anime.index')); ?>" onclick="toggleMobileMenu()" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 <?php echo e(request()->routeIs('anime.index') ? 'bg-gray-100 dark:bg-white/5 text-purple-600 dark:text-purple-400' : ''); ?>">Browse</a>
            <a href="<?php echo e(route('anime.popular')); ?>" onclick="toggleMobileMenu()" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 <?php echo e(request()->routeIs('anime.popular') ? 'bg-gray-100 dark:bg-white/5 text-purple-600 dark:text-purple-400' : ''); ?>">Popular</a>
            <a href="<?php echo e(route('anime.schedule')); ?>" onclick="toggleMobileMenu()" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 <?php echo e(request()->routeIs('anime.schedule') ? 'bg-gray-100 dark:bg-white/5 text-purple-600 dark:text-purple-400' : ''); ?>">Schedule</a>
            <?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(url('/dashboard')); ?>" onclick="toggleMobileMenu()" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">Dashboard</a>
            <?php else: ?>
                <div class="pt-4 border-t border-gray-200 dark:border-gray-800/60 space-y-2">
                    <button onclick="openAuthModal('login'); toggleMobileMenu();" class="block w-full px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">Log in</button>
                    <button onclick="openAuthModal('register'); toggleMobileMenu();" class="block w-full text-center px-3 py-2.5 rounded-lg text-sm font-medium bg-purple-600 text-white hover:bg-purple-700">Sign up</button>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php $__env->startPush('scripts'); ?>
<script>
function toggleTheme() {
    const html = document.documentElement;
    if (html.classList.contains('dark')) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const iconOpen = document.getElementById('menu-icon-open');
    const iconClose = document.getElementById('menu-icon-close');
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    iconOpen.classList.toggle('hidden');
    iconClose.classList.toggle('hidden');
    document.body.style.overflow = isOpen ? '' : 'hidden';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const menu = document.getElementById('mobile-menu');
        if (!menu.classList.contains('hidden')) toggleMobileMenu();
        closeAuthModal();
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\xampp\htdocs\AniMex\resources\views/layouts/partials/header.blade.php ENDPATH**/ ?>