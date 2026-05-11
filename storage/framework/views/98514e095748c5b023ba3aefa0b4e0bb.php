

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white mb-8">Anime Schedule</h1>

    <?php if(count($anime) === 0): ?>
        <div class="text-center py-16">
            <p class="text-gray-500 dark:text-gray-400 text-lg">No ongoing anime to display.</p>
        </div>
    <?php else: ?>
        <div class="space-y-10">
            <?php $__currentLoopData = $anime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $season => $seasonAnime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <section>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-800"><?php echo e($season); ?></h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php $__currentLoopData = $seasonAnime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(url('/anime/' . $item->slug)); ?>" class="group">
                            <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                                <?php if($item->cover_image): ?>
                                    <img src="<?php echo e(str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image)); ?>" alt="<?php echo e($item->title); ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold"><?php echo e(substr($item->title, 0, 1)); ?></div>
                                <?php endif; ?>
                                <span class="absolute top-2 left-2 bg-emerald-500/90 backdrop-blur-sm text-white text-[11px] px-2 py-0.5 rounded-md font-semibold">Ongoing</span>
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                                    <h3 class="text-sm font-medium text-white truncate"><?php echo e($item->title_english ?? $item->title); ?></h3>
                                    <?php if($item->genres->isNotEmpty()): ?>
                                        <p class="text-xs text-gray-300 mt-0.5 truncate"><?php echo e($item->genres->pluck('name')->join(', ')); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/anime/schedule.blade.php ENDPATH**/ ?>