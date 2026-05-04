<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> - AniMex Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        * { scrollbar-width: thin; scrollbar-color: #4b5563 transparent; }
        *::-webkit-scrollbar { width: 6px; }
        *::-webkit-scrollbar-track { background: transparent; }
        *::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 3px; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 font-[Inter] antialiased">
    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col flex-shrink-0">
            <div class="p-5 border-b border-gray-800">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-600 rounded-lg flex items-center justify-center font-bold text-white">A</div>
                    <span class="text-lg font-semibold">AniMex Admin</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?php echo e(request()->routeIs('admin.dashboard') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800'); ?> transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                    Dashboard
                </a>
                <a href="<?php echo e(route('admin.anime.index')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?php echo e(request()->routeIs('admin.anime.*') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800'); ?> transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-4 3V1m-4 0h8m-8 0H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2h-3m-4 3h4m-4 0l4 4m4-4l-4 4"/></svg>
                    Anime
                </a>
                <a href="<?php echo e(route('admin.import.search')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?php echo e(request()->routeIs('admin.import.*') ? 'bg-purple-600/10 text-purple-400' : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800'); ?> transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Import Anime
                </a>
                <a href="<?php echo e(url('/')); ?>" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:text-gray-200 hover:bg-gray-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    View Site
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-sm font-medium"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?php echo e(auth()->user()->name); ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo e(auth()->user()->email); ?></p>
                    </div>
                </div>
                <form action="<?php echo e(route('logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-red-400 hover:bg-gray-800 transition">Sign Out</button>
                </form>
            </div>
        </aside>

        
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <header class="bg-gray-900 border-b border-gray-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-xl font-semibold"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h1>
                    <?php echo $__env->yieldContent('header-actions'); ?>
                </div>
            </header>

            
            <main class="flex-1 overflow-y-auto p-6">
                <?php if(session('success')): ?>
                    <div class="mb-6 bg-green-600/10 border border-green-600/20 text-green-400 px-4 py-3 rounded-lg text-sm"><?php echo e(session('success')); ?></div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="mb-6 bg-red-600/10 border border-red-600/20 text-red-400 px-4 py-3 rounded-lg text-sm"><?php echo e(session('error')); ?></div>
                <?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\AniMex\resources\views/layouts/admin.blade.php ENDPATH**/ ?>