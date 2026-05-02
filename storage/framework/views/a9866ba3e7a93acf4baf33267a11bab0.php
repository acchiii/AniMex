<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white"><?php if(request('search')): ?>Results for "<?php echo e(request('search')); ?>"<?php else: ?> Browse Anime <?php endif; ?></h1>
    </div>

    <?php if($anime->isEmpty()): ?>
        <div class="text-center py-16">
            <p class="text-gray-500 dark:text-gray-400 text-lg"><?php if(request('search')): ?>No results found for "<?php echo e(request('search')); ?>".<?php else: ?> No anime found.<?php endif; ?></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php $__currentLoopData = $anime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $item->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                        <?php if($item->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image)); ?>" alt="<?php echo e($item->title); ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold"><?php echo e(substr($item->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <?php if($item->status === 'ongoing'): ?>
                            <span class="absolute top-2 left-2 bg-emerald-500/90 backdrop-blur-sm text-white text-[11px] px-2 py-0.5 rounded-md font-semibold">Ongoing</span>
                        <?php elseif($item->status === 'completed'): ?>
                            <span class="absolute top-2 left-2 bg-blue-500/90 backdrop-blur-sm text-white text-[11px] px-2 py-0.5 rounded-md font-semibold">Completed</span>
                        <?php endif; ?>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                            <h3 class="text-sm font-medium text-white truncate"><?php echo e($item->title_english ?? $item->title); ?></h3>
                            <?php if($item->score): ?>
                                <p class="text-xs text-amber-400 mt-0.5 font-medium">★ <?php echo e(number_format($item->score, 1)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-8">
            <?php echo e($anime->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/anime/index.blade.php ENDPATH**/ ?>