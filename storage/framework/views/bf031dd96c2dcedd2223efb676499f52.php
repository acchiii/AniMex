

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Video Player -->
    <div class="aspect-video bg-black rounded-lg overflow-hidden mb-4">
        <?php if($episode->sources->count()): ?>
            <video id="video-player" class="w-full h-full" controls preload="metadata">
                <source src="<?php echo e($episode->sources->first()->url); ?>" type="<?php echo e($episode->sources->first()->mime_type ?? 'video/mp4'); ?>">
                <?php $__currentLoopData = $episode->subtitles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subtitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <track label="<?php echo e($subtitle->label); ?>" kind="subtitles" srclang="<?php echo e($subtitle->language); ?>" src="<?php echo e($subtitle->url); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                Your browser does not support the video tag.
            </video>
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <p>No video sources available</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Episode Info -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-white"><?php echo e($anime->title_english ?: $anime->title); ?></h1>
            <p class="text-gray-400">Episode <?php echo e($episode->number); ?>: <?php echo e($episode->title); ?></p>
        </div>
        <div class="flex items-center gap-2">
            <?php if($prevEpisode): ?>
                <a href="<?php echo e(route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $prevEpisode->number])); ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg transition">
                    ← Previous
                </a>
            <?php endif; ?>
            <?php if($nextEpisode): ?>
                <a href="<?php echo e(route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $nextEpisode->number])); ?>" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg transition">
                    Next →
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Source Selection -->
    <?php if($episode->sources->count() > 1): ?>
        <div class="mb-4">
            <h3 class="text-sm font-medium text-gray-300 mb-2">Video Source</h3>
            <div class="flex flex-wrap gap-2">
                <?php $__currentLoopData = $episode->sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button onclick="changeSource('<?php echo e($source->url); ?>', '<?php echo e($source->mime_type ?? 'video/mp4'); ?>')" 
                        class="px-3 py-1 bg-gray-800 hover:bg-gray-700 rounded text-sm <?php echo e($index === 0 ? 'ring-2 ring-purple-500' : ''); ?>">
                        <?php echo e($source->quality); ?> - <?php echo e($source->server); ?>

                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Subtitle Selection -->
    <?php if($episode->subtitles->count() > 0): ?>
        <div class="mb-4">
            <h3 class="text-sm font-medium text-gray-300 mb-2">Subtitles</h3>
            <div class="flex flex-wrap gap-2">
                <button onclick="disableSubtitles()" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 rounded text-sm">Off</button>
                <?php $__currentLoopData = $episode->subtitles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subtitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button onclick="enableSubtitle('<?php echo e($subtitle->language); ?>')" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 rounded text-sm">
                        <?php echo e($subtitle->label); ?>

                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Back to Anime -->
    <div class="mt-4">
        <a href="<?php echo e(route('anime.show', $anime->slug)); ?>" class="text-purple-500 hover:text-purple-400">
            ← Back to <?php echo e($anime->title_english ?: $anime->title); ?>

        </a>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
const player = document.getElementById('video-player');

function changeSource(url, mimeType) {
    if (player) {
        player.src = url;
        player.load();
        player.play();
    }
}

function disableSubtitles() {
    if (player) {
        for (let track of player.textTracks) {
            track.mode = 'disabled';
        }
    }
}

function enableSubtitle(language) {
    if (player) {
        for (let track of player.textTracks) {
            track.mode = (track.language === language) ? 'showing' : 'hidden';
        }
    }
}

// Save progress every 10 seconds
if (player) {
    player.addEventListener('timeupdate', function() {
        if (Math.floor(player.currentTime) % 10 === 0) {
            saveProgress(Math.floor(player.currentTime), Math.floor(player.duration));
        }
    });
}

function saveProgress(progress, duration) {
    fetch('<?php echo e(route("home")); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
        },
        body: JSON.stringify({ 
            episode_id: <?php echo e($episode->id); ?>,
            progress: progress,
            duration: duration 
        })
    });
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/anime/stream.blade.php ENDPATH**/ ?>