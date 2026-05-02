<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-white"><?php if(request('search')): ?>Results for "<?php echo e(request('search')); ?>"<?php else: ?> Browse Anime <?php endif; ?></h1>
    </div>

    <?php if($anime->isEmpty()): ?>
        <div class="text-center py-12">
            <p class="text-gray-400 text-lg"><?php if(request('search')): ?>No results found for "<?php echo e(request('search')); ?>".<?php else: ?> No anime found.<?php endif; ?></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php $__currentLoopData = $anime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $item->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        <?php if($item->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image)); ?>" alt="<?php echo e($item->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold"><?php echo e(substr($item->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <?php if($item->status === 'ongoing'): ?>
                            <span class="absolute top-2 left-2 bg-green-600 text-white text-xs px-2 py-1 rounded">Ongoing</span>
                        <?php elseif($item->status === 'completed'): ?>
                            <span class="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">Completed</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate"><?php echo e($item->title_english ?? $item->title); ?></h3>
                    <?php if($item->score): ?>
                        <p class="text-xs text-yellow-400 mt-1">★ <?php echo e(number_format($item->score, 1)); ?></p>
                    <?php endif; ?>
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