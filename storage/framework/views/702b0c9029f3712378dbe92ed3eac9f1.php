<?php $__env->startSection('content'); ?>
<?php
    $coverUrl = $anime->cover_image 
        ? (str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image))
        : asset('images/placeholder-cover.jpg');
    $bannerUrl = $anime->banner_image 
        ? (str_starts_with($anime->banner_image, 'http') ? $anime->banner_image : asset('storage/' . $anime->banner_image))
        : $coverUrl;
?>

<div class="relative">
    <!-- Banner Background -->
    <div class="absolute inset-0 h-[50vh] overflow-hidden">
        <img src="<?php echo e($bannerUrl); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-50 dark:from-gray-950 via-gray-50/80 dark:via-gray-950/80 to-transparent"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Cover Image -->
            <div class="flex-shrink-0 w-full md:w-64">
                <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($anime->title); ?>" class="w-full rounded-lg shadow-xl">
            </div>

            <!-- Anime Info -->
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <?php if($anime->status === 'ongoing'): ?>
                        <span class="bg-green-600 text-white text-xs px-2 py-1 rounded">Ongoing</span>
                    <?php elseif($anime->status === 'completed'): ?>
                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded">Completed</span>
                    <?php endif; ?>
                    <span class="text-gray-500 dark:text-gray-400 text-sm"><?php echo e($anime->type); ?></span>
                    <?php if($anime->episodes_count): ?>
                        <span class="text-gray-500 dark:text-gray-400 text-sm"><?php echo e($anime->episodes_count); ?> episodes</span>
                    <?php endif; ?>
                </div>

                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?php echo e($anime->title_english ?: $anime->title); ?></h1>
                <?php if($anime->title_english): ?>
                    <p class="text-gray-500 dark:text-gray-400 mb-4"><?php echo e($anime->title_japanese); ?></p>
                <?php endif; ?>

                <!-- Rating & Actions -->
                <div class="flex items-center gap-4 mb-4">
                    <?php if($anime->score): ?>
                        <div class="flex items-center gap-1">
                            <span class="text-yellow-500">★</span>
                            <span class="text-gray-900 dark:text-white font-medium"><?php echo e(number_format($anime->score, 1)); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(auth()->guard()->check()): ?>
                        <button onclick="toggleFavorite(<?php echo e($anime->id); ?>)" 
                            class="flex items-center gap-1 px-3 py-1 rounded <?php echo e($isFavorite ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'); ?> hover:bg-opacity-80 transition">
                            <span><?php echo e($isFavorite ? '♥' : '♡'); ?></span>
                            <span class="text-sm"><?php echo e($isFavorite ? 'Favorited' : 'Favorite'); ?></span>
                        </button>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-1" id="rating-section">
                            <?php for($i = 1; $i <= 10; $i++): ?>
                                <button onclick="rateAnime(<?php echo e($anime->id); ?>, <?php echo e($i); ?>)" 
                                    class="text-<?php echo e($userRating && $userRating->score >= $i ? 'yellow' : 'gray'); ?>-500 hover:text-yellow-400 transition">
                                    ★
                                </button>
                            <?php endfor; ?>
                            <?php if($userRating): ?>
                                <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">Your rating: <?php echo e($userRating->score); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Genres -->
                <?php if($anime->genres->count()): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php $__currentLoopData = $anime->genres; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $genre): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route('anime.genre', $genre->slug)); ?>" 
                                class="bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 px-3 py-1 rounded text-sm text-gray-700 dark:text-gray-300 dark:hover:text-white transition">
                                <?php echo e($genre->name); ?>

                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <!-- Synopsis -->
                <p class="text-gray-600 dark:text-gray-300 mb-4"><?php echo e($anime->synopsis); ?></p>

                <!-- Studio -->
                <?php if($anime->studio): ?>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Studio: <a href="<?php echo e(route('anime.studio', $anime->studio->slug)); ?>" class="text-purple-600 dark:text-purple-500 hover:text-purple-700 dark:hover:text-purple-400"><?php echo e($anime->studio->name); ?></a></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Episodes List -->
        <?php if($anime->episodes->count()): ?>
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Episodes</h2>
                <div class="space-y-2">
                    <?php $__currentLoopData = $anime->episodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $episode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $episode->number])); ?>" 
                            class="flex items-center gap-4 p-3 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition">
                            <span class="text-gray-500 dark:text-gray-400 w-12">EP <?php echo e($episode->number); ?></span>
                            <span class="flex-1 text-gray-900 dark:text-white"><?php echo e($episode->title); ?></span>
                            <?php if($episode->is_filler): ?>
                                <span class="text-xs bg-yellow-600 text-white px-2 py-0.5 rounded">Filler</span>
                            <?php endif; ?>
                            <?php if($episode->sources->count()): ?>
                                <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo e($episode->sources->count()); ?> sources</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Related Anime -->
        <?php if($related->count()): ?>
            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Related Anime</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php $__currentLoopData = $related; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('anime.show', $item->slug)); ?>" class="group">
                            <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-800">
                                <?php if($item->cover_image): ?>
                                    <img src="<?php echo e(str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/' . $item->cover_image)); ?>" alt="<?php echo e($item->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-gray-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 truncate"><?php echo e($item->title); ?></h3>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if(auth()->guard()->check()): ?>
<?php $__env->startPush('scripts'); ?>
<script>
function toggleFavorite(animeId) {
    fetch(`/anime/${animeId}/favorite`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.favorited) {
            window.location.reload();
        } else {
            window.location.reload();
        }
    });
}

function rateAnime(animeId, score) {
    fetch(`/anime/${animeId}/rate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
        },
        body: JSON.stringify({ score })
    })
    .then(res => res.json())
    .then(data => {
        window.location.reload();
    });
}
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/anime/show.blade.php ENDPATH**/ ?>