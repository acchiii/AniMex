<?php $__env->startSection('title', 'Anime'); ?>
<?php $__env->startSection('page-title', 'Manage Anime'); ?>

<?php $__env->startSection('header-actions'); ?>
<a href="<?php echo e(route('admin.anime.create')); ?>" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">Add Anime</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full">
        <thead class="border-b border-gray-800">
            <tr>
                <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Cover</th>
                <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Title</th>
                <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Type</th>
                <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Status</th>
                <th class="text-left px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider hidden lg:table-cell">Episodes</th>
                <th class="text-right px-5 py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            <?php $__empty_1 = true; $__currentLoopData = $anime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-800/50 transition">
                    <td class="px-5 py-3">
                        <div class="w-10 h-14 rounded overflow-hidden bg-gray-800">
                            <?php if($item->cover_image): ?>
                                <img src="<?php echo e(str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image)); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs font-bold"><?php echo e(substr($item->title, 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <p class="text-sm font-medium"><?php echo e($item->title_english ?: $item->title); ?></p>
                        <?php if($item->title_english): ?>
                            <p class="text-xs text-gray-500"><?php echo e($item->title); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 hidden md:table-cell">
                        <span class="text-sm text-gray-400"><?php echo e($item->type); ?></span>
                    </td>
                    <td class="px-5 py-3 hidden md:table-cell">
                        <span class="text-xs px-2 py-0.5 rounded font-medium
                            <?php if($item->status === 'ongoing'): ?> bg-green-600/20 text-green-400
                            <?php elseif($item->status === 'completed'): ?> bg-blue-600/20 text-blue-400
                            <?php elseif($item->status === 'upcoming'): ?> bg-yellow-600/20 text-yellow-400
                            <?php else: ?> bg-red-600/20 text-red-400
                            <?php endif; ?>"><?php echo e(ucfirst($item->status)); ?></span>
                    </td>
                    <td class="px-5 py-3 hidden lg:table-cell">
                        <span class="text-sm text-gray-400"><?php echo e($item->episodes_count ?? 0); ?> <?php if($item->episodes_count_count): ?> / <?php echo e($item->episodes_count_count); ?> added <?php endif; ?></span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?php echo e(route('admin.anime.episodes', $item)); ?>" class="text-xs text-gray-400 hover:text-gray-200 px-2 py-1 rounded hover:bg-gray-700 transition">Episodes</a>
                            <a href="<?php echo e(route('admin.anime.edit', $item)); ?>" class="text-xs text-purple-400 hover:text-purple-300 px-2 py-1 rounded hover:bg-gray-700 transition">Edit</a>
                            <form action="<?php echo e(route('admin.anime.destroy', $item)); ?>" method="POST" onsubmit="return confirm('Delete this anime?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-xs text-red-400 hover:text-red-300 px-2 py-1 rounded hover:bg-gray-700 transition">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-gray-500">No anime found. <a href="<?php echo e(route('admin.anime.create')); ?>" class="text-purple-400 hover:underline">Add one</a></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if($anime->hasPages()): ?>
        <div class="px-5 py-4 border-t border-gray-800">
            <?php echo e($anime->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/admin/anime/index.blade.php ENDPATH**/ ?>