

<?php $__env->startSection('content'); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if(session('warning')): ?>
            <div class="mb-4 p-3 bg-yellow-900/50 border border-yellow-700 rounded-lg text-yellow-200 text-sm">
                <?php echo e(session('warning')); ?>

            </div>
        <?php endif; ?>
        <div class="aspect-video bg-black rounded-lg overflow-hidden mb-4 relative" id="player-container">
            <?php if($episode->sources->count()): ?>
                <video id="video-player" class="w-full h-full <?php if($episode->sources->first()->type === 'embed'): ?> hidden <?php endif; ?>"
                    controls preload="metadata">
                    <?php $__currentLoopData = $episode->subtitles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $subParsed = parse_url($sub->file_path);
                            $isExternal = $subParsed && isset($subParsed['host']) && $subParsed['host'] !== request()->getHost();
                            $subUrl = $isExternal ? route('proxy.subtitle', ['url' => $sub->file_path]) : $sub->file_path;
                        ?>
                        <track kind="subtitles" src="<?php echo e($subUrl); ?>" srclang="<?php echo e($sub->language); ?>" label="<?php echo e($sub->label); ?>" <?php if($sub->is_default): ?> default <?php endif; ?>>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </video>
                <iframe id="embed-player" class="w-full h-full <?php if($episode->sources->first()->type !== 'embed'): ?> hidden <?php endif; ?>"
                    frameborder="0" allowfullscreen></iframe>
                <div id="play-overlay"
                    class="absolute inset-0 flex items-center justify-center bg-black/60 cursor-pointer hidden"
                    onclick="playVideo()">
                    <svg class="w-20 h-20 text-white opacity-80 hover:opacity-100 transition" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </div>
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                            </path></video>
                            <p>No video sources available</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($anime->title_english ?: $anime->title); ?>

                </h1>
                <p class="text-gray-600 dark:text-gray-400">Episode <?php echo e($episode->number); ?>: <?php echo e($episode->title); ?></p>
            </div>
            <div class="flex items-center gap-2">
                <?php if($prevEpisode): ?>
                    <a href="<?php echo e(route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $prevEpisode->number])); ?>"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded-lg transition text-gray-900 dark:text-white">
                        ← Previous
                    </a>
                <?php endif; ?>
                <?php if($nextEpisode): ?>
                    <a href="<?php echo e(route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $nextEpisode->number])); ?>"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded-lg transition text-gray-900 dark:text-white">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if($episode->sources->count() > 1): ?>
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Video Source</h3>
                <div class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = $episode->sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button onclick="changeSource(<?php echo e($index); ?>)"
                            class="source-btn px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 <?php echo e($index === 0 ? 'ring-2 ring-purple-500' : ''); ?>"
                            data-index="<?php echo e($index); ?>">
                            <?php echo e($source->quality); ?><?php if($source->server): ?> - <?php echo e($source->server->name); ?><?php endif; ?>
                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-4 hidden" id="quality-section">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quality</h3>
            <div id="quality-selector" class="flex flex-wrap gap-2">
                <button onclick="setQuality(-1)" class="quality-btn px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 ring-2 ring-purple-500" data-level="-1">Auto</button>
            </div>
        </div>

        <?php if($episode->subtitles->count() > 0): ?>
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subtitles</h3>
                <div class="flex flex-wrap gap-2">
                    <button onclick="disableSubtitles()"
                        class="px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300">Off</button>
                    <?php $__currentLoopData = $episode->subtitles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subtitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button onclick="enableSubtitle('<?php echo e($subtitle->language); ?>')"
                            class="px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300">
                            <?php echo e($subtitle->label); ?>

                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if($episodes->count() > 1): ?>
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Episodes</h3>
                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-1.5 max-h-48 overflow-y-auto p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <?php $__currentLoopData = $episodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('anime.stream', ['slug' => $anime->slug, 'episodeNumber' => $ep->number])); ?>"
                            class="px-2 py-1.5 text-center text-xs rounded transition
                                <?php if($ep->id === $episode->id): ?>
                                    bg-purple-600 text-white ring-2 ring-purple-400
                                <?php else: ?>
                                    bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600
                                <?php endif; ?>">
                            <?php echo e($ep->number); ?>

                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="<?php echo e(route('anime.show', $anime->slug)); ?>"
                class="text-purple-600 dark:text-purple-500 hover:text-purple-700 dark:hover:text-purple-400">
                ← Back to <?php echo e($anime->title_english ?: $anime->title); ?>

            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <?php
        $sourcesData = $episode->sources->map(fn($s) => [
            'id' => $s->id,
            'url' => $s->url,
            'type' => $s->type ?? 'mp4',
            'headers' => $s->headers ?? [],
        ])->values()->all();

        $proxySubtitleUrl = route('proxy.subtitle');
        $subtitlesData = $episode->subtitles->map(fn($s) => [
            'language' => $s->language,
            'file_path' => $s->file_path,
            'proxy_path' => (function ($url) use ($proxySubtitleUrl) {
                $parsed = parse_url($url);
                return ($parsed && isset($parsed['host']) && $parsed['host'] !== request()->getHost())
                    ? $proxySubtitleUrl . '?url=' . urlencode($url)
                    : $url;
            })($s->file_path),
        ])->values()->all();
    ?>
    <script>
        const sources = <?php echo json_encode($sourcesData, 15, 512) ?>;
        const subtitles = <?php echo json_encode($subtitlesData, 15, 512) ?>;

        let hls = null;
        const player = document.getElementById('video-player');
        const embedPlayer = document.getElementById('embed-player');
        const playOverlay = document.getElementById('play-overlay');

        function playVideo() {
            playOverlay.classList.add('hidden');
            if (hls) { hls.startLoad(); }
            player.play().catch(function (e) {
                console.warn('playVideo failed:', e);
            });
        }

        function loadSource(index) {
            const src = sources[index];
            if (!src) return;

            console.log('Loading source', index, src);

            if (hls) { hls.destroy(); hls = null; }

            player.classList.add('hidden');
            embedPlayer.classList.add('hidden');
            player.removeAttribute('src');
            embedPlayer.src = '';

            if (src.type === 'embed') {
                embedPlayer.src = src.url;
                embedPlayer.classList.remove('hidden');
                document.getElementById('quality-section')?.classList.add('hidden');
                return;
            }

            player.classList.remove('hidden');

            const proxyUrl = '<?php echo e(route("proxy.source", "")); ?>/' + src.id;
            const isHls = src.type === 'hls' || String(src.url).match(/\.m3u8/i);

            if (isHls && Hls.isSupported()) {
                hls = new Hls({
                    xhrSetup: function (xhr, url) {
                        for (const [key, val] of Object.entries(src.headers || {})) {
                            try { xhr.setRequestHeader(key, val); } catch (e) { }
                        }
                        try {
                            xhr.setRequestHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                        } catch (e) { }
                    },
                    maxBufferLength: 60,
                    maxMaxBufferLength: 300,
                    maxBufferSize: 100 * 1000 * 1000,
                    maxBufferHole: 2,
                    abrEwmaDefaultEstimate: 1000000,
                    abrEwmaFastVoD: 4.0,
                    abrEwmaSlowVoD: 12.0,
                    lowLatencyMode: false,
                    capLevelToPlayerSize: true,
                    backbufferLength: 60,
                });
                hls.loadSource(src.url);
                hls.attachMedia(player);
                hls.on(Hls.Events.MANIFEST_PARSED, function () {
                    buildQualitySelector();
                    player.play().catch(function () {
                        playOverlay.classList.remove('hidden');
                    });
                });
                hls.on(Hls.Events.ERROR, function (event, data) {
                    console.warn('HLS error:', data.type, data.details, data.fatal);
                    if (data.fatal) {
                        if (data.details === 'manifestLoadError' || data.details === 'levelLoadError') {
                            console.warn('Direct source failed, trying proxy...');
                            hls.loadSource(proxyUrl);
                            hls.on(Hls.Events.MANIFEST_PARSED, function () {
                                player.play().catch(function () {
                                    playOverlay.classList.remove('hidden');
                                });
                            });
                        } else if (['bufferStalledError', 'bufferSeekOverHole', 'bufferAppendError', 'fragParsingError'].includes(data.details)) {
                            console.warn('Buffer error, restarting load...');
                            hls.startLoad();
                        }
                    }
                });
                return;
            }

            document.getElementById('quality-section')?.classList.add('hidden');

            player.src = proxyUrl;
            player.load();
            player.play().catch(function () {
                playOverlay.classList.remove('hidden');
            });
        }

        function setQuality(level) {
            if (!hls) return;
            hls.currentLevel = level;
            document.querySelectorAll('.quality-btn').forEach(function (btn) {
                btn.classList.remove('ring-2', 'ring-purple-500');
                if (parseInt(btn.dataset.level) === level) {
                    btn.classList.add('ring-2', 'ring-purple-500');
                }
            });
        }

        function buildQualitySelector() {
            var container = document.getElementById('quality-selector');
            var section = document.getElementById('quality-section');
            if (!container || !section || !hls) return;
            container.innerHTML = '<button onclick="setQuality(-1)" class="quality-btn px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300 ring-2 ring-purple-500" data-level="-1">Auto</button>';
            hls.levels.forEach(function (level, i) {
                var label = level.height ? level.height + 'p' : (level.name ? level.name : 'Quality ' + i);
                var btn = document.createElement('button');
                btn.className = 'quality-btn px-3 py-1 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 rounded text-sm text-gray-700 dark:text-gray-300';
                btn.dataset.level = i;
                btn.textContent = label;
                btn.onclick = function () { setQuality(i); };
                container.appendChild(btn);
            });
            section.classList.remove('hidden');
        }

        function changeSource(index) {
            playOverlay.classList.add('hidden');
            loadSource(index);
            document.querySelectorAll('.source-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-purple-500');
            });
            document.querySelector(`.source-btn[data-index="${index}"]`)?.classList.add('ring-2', 'ring-purple-500');
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

        document.addEventListener('DOMContentLoaded', function () {
            if (sources.length > 0) {
                loadSource(0);
            }
        });

        function enableEnglishSubs() {
            if (player) {
                for (let track of player.textTracks) {
                    track.mode = track.language === 'en' ? 'showing' : 'hidden';
                }
            }
        }

        if (player) {
            player.addEventListener('timeupdate', function () {
                if (Math.floor(player.currentTime) % 10 === 0) {
                    saveProgress(Math.floor(player.currentTime), Math.floor(player.duration));
                }
            });
            player.addEventListener('error', function () {
                console.warn('Video error:', player.error ? player.error.message : 'unknown', 'code:', player.error ? player.error.code : 'none');
            });
            player.addEventListener('loadedmetadata', function () {
                console.log('Video loaded, duration:', player.duration);
                enableEnglishSubs();
            });
        }

        function saveProgress(progress, duration) {
            fetch('<?php echo e(route("progress.save")); ?>', {
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
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/anime/stream.blade.php ENDPATH**/ ?>