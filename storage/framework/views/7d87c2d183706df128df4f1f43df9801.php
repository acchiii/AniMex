

<?php $__env->startSection('title', 'Import Anime'); ?>
<?php $__env->startSection('page-title', 'Import from Jikan/MyAnimeList'); ?>

<?php $__env->startSection('header-actions'); ?>
<a href="<?php echo e(route('admin.anime.index')); ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 text-sm transition">&larr; Back to Anime</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<form action="<?php echo e(route('admin.import.search')); ?>" method="GET" class="mb-6">
    <div class="flex gap-3">
        <input
            type="text"
            name="q"
            value="<?php echo e($query ?? ''); ?>"
            placeholder="Search anime..."
            required
            class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-600"
        >
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2.5 rounded-lg text-sm font-medium transition">Search</button>
    </div>
</form>

<?php if(isset($parsed) && count($parsed) > 0): ?>
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
        <div class="divide-y divide-gray-200 dark:divide-gray-800">
            <?php $__currentLoopData = $parsed; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex items-center gap-4 px-5 py-4">
                    <img
                        src="<?php echo e($item['image']); ?>"
                        class="w-16 h-24 rounded-lg object-cover flex-shrink-0 bg-gray-100 dark:bg-gray-800"
                        alt="<?php echo e($item['title']); ?>"
                    >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($item['title_english'] ?: $item['title']); ?></p>
                        <?php if($item['title'] !== $item['title_english']): ?>
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate"><?php echo e($item['title']); ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            <?php echo e($item['type']); ?> &middot; <?php echo e($item['episodes']); ?> eps &middot; Score: <?php echo e($item['score']); ?> &middot; <?php echo e($item['status']); ?>

                        </p>
                    </div>
                    <form action="<?php echo e(route('admin.import.anime', $item['mal_id'])); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">Import</button>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php elseif(isset($query)): ?>
    <div class="text-center py-16 text-gray-600 dark:text-gray-400">
        <p class="text-lg">No results found for "<?php echo e($query); ?>".</p>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/admin/jikan/search.blade.php ENDPATH**/ ?>