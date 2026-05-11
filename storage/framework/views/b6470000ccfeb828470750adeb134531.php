

<?php $__env->startSection('title', 'Episodes'); ?>
<?php $__env->startSection('page-title', 'Episodes: ' . ($anime->title_english ?: $anime->title)); ?>

<?php $__env->startSection('header-actions'); ?>
<div class="flex items-center gap-2">
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
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">AniList ID: <?php echo e($anime->anilist_id); ?></span>
                <form action="<?php echo e(route('admin.anime.fetch-episode-list', $anime)); ?>" method="POST" class="inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-xs bg-purple-600 hover:bg-purple-700 px-2.5 py-1.5 rounded-lg transition font-medium text-white">
                        Fetch Episodes
                    </button>
                </form>
                <form action="<?php echo e(route('admin.anime.import-all-sources', $anime)); ?>" method="POST" class="inline" onsubmit="return confirm('Import sources for ALL episodes without sources? This may take a while.')">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-xs bg-green-600 hover:bg-green-700 px-2.5 py-1.5 rounded-lg transition font-medium text-white">
                        Import All Sources
                    </button>
                </form>
            </div>
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

                        
                        <?php if($ep->sources->isNotEmpty()): ?>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <?php $__currentLoopData = $ep->sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded px-1.5 py-0.5 text-xs">
                                        <span class="text-gray-700 dark:text-gray-300"><?php echo e($source->quality); ?></span>
                                        <span class="text-gray-400">|</span>
                                        <span class="text-gray-600 dark:text-gray-400"><?php echo e($source->type); ?></span>
                                        <span class="text-gray-400">|</span>
                                        <span class="text-gray-600 dark:text-gray-400"><?php echo e($source->language); ?></span>
                                        <form action="<?php echo e(route('admin.anime.episodes.sources.destroy', [$anime, $ep, $source])); ?>" method="POST" class="inline" onsubmit="return confirm('Delete this source?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-500 hover:text-red-700 ml-1">&times;</button>
                                        </form>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        <?php if($ep->is_subbed): ?><span class="text-blue-700 dark:text-blue-400">SUB</span><?php endif; ?>
                        <?php if($ep->is_dubbed): ?><span class="text-green-700 dark:text-green-400 ml-1">DUB</span><?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <?php if($anime->anilist_id): ?>
                            <form action="<?php echo e(route('admin.anime.episodes.import-sources', [$anime, $ep])); ?>" method="POST" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 px-2.5 py-1.5 rounded-lg transition font-medium text-white">
                                    Import
                                </button>
                            </form>
                        <?php endif; ?>
                        <button onclick="toggleSourceForm(<?php echo e($ep->id); ?>)" class="text-xs bg-gray-600 hover:bg-gray-700 px-2.5 py-1.5 rounded-lg transition font-medium text-white">
                            + Source
                        </button>
                    </div>
                </div>

                
                <div id="source-form-<?php echo e($ep->id); ?>" class="hidden mt-3 pl-14">
                    <p class="text-xs text-gray-500 mb-2">For embed sources (recommended), set Type to "Embed" and enter the iframe URL. For direct MP4/HLS, add required headers.</p>
                    <form action="<?php echo e(route('admin.anime.episodes.sources.store', [$anime, $ep])); ?>" method="POST" class="flex flex-wrap items-end gap-2">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">URL *</label>
                            <input type="url" name="url" placeholder="https://..." required class="w-72 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-600">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Quality</label>
                            <select name="quality" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="360p">360p</option>
                                <option value="480p">480p</option>
                                <option value="720p" selected>720p</option>
                                <option value="1080p">1080p</option>
                                <option value="4K">4K</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Type</label>
                            <select name="type" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="embed">Embed (recommended)</option>
                                <option value="mp4">MP4</option>
                                <option value="hls">HLS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Lang</label>
                            <select name="language" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="sub">Sub</option>
                                <option value="dub">Dub</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Headers (JSON)</label>
                            <input type="text" name="headers" placeholder='{"Referer":"https://..."}' class="w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-purple-600 font-mono">
                        </div>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-3 py-1.5 rounded-lg text-xs font-medium transition text-white">Add</button>
                    </form>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="px-5 py-12 text-center text-gray-600 dark:text-gray-400 text-sm">No episodes yet. Add one above.</p>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function toggleSourceForm(episodeId) {
    const form = document.getElementById('source-form-' + episodeId);
    form.classList.toggle('hidden');
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/admin/anime/episodes.blade.php ENDPATH**/ ?>