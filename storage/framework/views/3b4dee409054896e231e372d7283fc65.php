<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-white mb-8">🔥 Most Popular</h1>

    <?php if($anime->isEmpty()): ?>
        <div class="text-center py-12">
            <p class="text-gray-400 text-lg">No anime found.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php $__currentLoopData = $anime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $item->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        <?php if($item->cover_image): ?>
                            <img src="<?php echo e($item->cover_image); ?>" alt="<?php echo e($item->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold"><?php echo e(substr($item->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="absolute top-2 left-2 bg-purple-600 text-white text-xs px-2 py-1 rounded font-medium">#<?php echo e($loop->iteration); ?></span>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate"><?php echo e($item->title_english ?? $item->title); ?></h3>
                    <p class="text-xs text-gray-500 mt-1"><?php echo e(number_format($item->views_count)); ?> views</p>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-8">
            <?php echo e($anime->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/anime/popular.blade.php ENDPATH**/ ?>