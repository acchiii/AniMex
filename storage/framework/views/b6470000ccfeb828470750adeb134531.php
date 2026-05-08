<?php $__env->startSection('title', 'Episodes'); ?>
<?php $__env->startSection('page-title', 'Episodes: ' . ($anime->title_english ?: $anime->title)); ?>

<?php $__env->startSection('header-actions'); ?>
<div class="flex items-center gap-2">
    <?php if($anime->anilist_id): ?>
        <form action="<?php echo e(route('admin.anime.import-all-sources', $anime)); ?>" method="POST" onsubmit="return confirm('Import sources for all episodes? This may take a while.')">
            <?php echo csrf_field(); ?>
            <button type="submit" class="bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg text-xs font-medium transition">Import All Sources</button>
        </form>
    <?php endif; ?>
    <a href="<?php echo e(route('admin.anime.index')); ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 text-sm transition">&larr; Back</a>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-4">Add Episode</h3>
    <form action="<?php echo e(route('admin.anime.episodes.store', $anime)); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Number *</label>
                <input type="number" name="number" value="<?php echo e(old('number')); ?>" min="1" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div class="col-span-2">
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Title</label>
                <input type="text" name="title" value="<?php echo e(old('title')); ?>" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Duration (sec)</label>
                <input type="number" name="duration" value="<?php echo e(old('duration')); ?>" min="0" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
        </div>
        <div class="flex flex-wrap gap-4 mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_filler" value="1" <?php echo e(old('is_filler') ? 'checked' : ''); ?> class="rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-600 dark:text-gray-400">Filler</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_recap" value="1" <?php echo e(old('is_recap') ? 'checked' : ''); ?> class="rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-600 dark:text-gray-400">Recap</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_subbed" value="1" <?php echo e(old('is_subbed', true) ? 'checked' : ''); ?> class="rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-600 dark:text-gray-400">Subbed</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_dubbed" value="1" <?php echo e(old('is_dubbed') ? 'checked' : ''); ?> class="rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-600 dark:text-gray-400">Dubbed</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_premium_only" value="1" <?php echo e(old('is_premium_only') ? 'checked' : ''); ?> class="rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-600 dark:text-gray-400">Premium</span>
            </label>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">Add Episode</button>
    </form>
</div>


<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">
            <?php echo e($episodes->count()); ?> Episode<?php echo e($episodes->count() !== 1 ? 's' : ''); ?>

        </h3>
        <?php if($anime->anilist_id): ?>
            <span class="text-xs text-gray-500">AniList ID: <?php echo e($anime->anilist_id); ?></span>
        <?php endif; ?>
    </div>

    <div class="divide-y divide-gray-200 dark:divide-gray-800">
        <?php $__empty_1 = true; $__currentLoopData = $episodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="px-5 py-3">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-purple-600/10 rounded-lg flex items-center justify-center text-sm font-medium text-purple-600 dark:text-purple-300 flex-shrink-0"><?php echo e($ep->number); ?></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($ep->title ?: 'Episode ' . $ep->number); ?></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            <?php if($ep->duration): ?><?php echo e(floor($ep->duration / 60)); ?>:<?php echo e(str_pad($ep->duration % 60, 2, '0', STR_PAD_LEFT)); ?><?php endif; ?>
                            <?php if($ep->is_filler): ?><span class="ml-2 text-yellow-600 dark:text-yellow-400">Filler</span><?php endif; ?>
                            <?php if($ep->is_recap): ?><span class="ml-2 text-gray-500 dark:text-gray-400">Recap</span><?php endif; ?>
                            <?php if($ep->sources->isNotEmpty()): ?>
                                <span class="ml-2 text-green-600 dark:text-green-400"><?php echo e($ep->sources->count()); ?> source(s)</span>
                            <?php else: ?>
                                <span class="ml-2 text-red-500">No sources</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <?php if($ep->is_subbed): ?><span class="text-blue-700 dark:text-blue-400">SUB</span><?php endif; ?>
                        <?php if($ep->is_dubbed): ?><span class="text-green-700 dark:text-green-400 ml-1">DUB</span><?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <?php if($anime->anilist_id): ?>
                            <form action="<?php echo e(route('admin.anime.episodes.import-sources', [$anime, $ep])); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-xs bg-purple-600 hover:bg-purple-700 px-2.5 py-1.5 rounded-lg transition font-medium">
                                    <?php echo e($ep->sources->isEmpty() ? 'Import' : 'Re-import'); ?>

                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="px-5 py-12 text-center text-gray-600 dark:text-gray-400 text-sm">No episodes yet. Add one above.</p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/admin/anime/episodes.blade.php ENDPATH**/ ?>