

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
                <div id="subtitle-hud"></div>
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

        <?php if($bannerAd): ?>
        <div class="mb-4 flex justify-center">
            <div class="max-w-lg w-full">
                <?php echo $__env->make('ads.slot', ['ad' => $bannerAd], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
        <?php endif; ?>

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
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Subtitles</h3>
                    <button id="sub-settings-btn" onclick="toggleSubSettings()" class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="Subtitle settings">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2 items-center" id="subtitle-buttons">
                    <button onclick="selectSubtitle(null)"
                        class="sub-btn sub-off px-3 py-1 rounded text-sm transition">Off</button>
                    <?php $__currentLoopData = $episode->subtitles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subtitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center gap-1">
                            <button onclick="selectSubtitle('<?php echo e($subtitle->language); ?>')"
                                class="sub-btn sub-<?php echo e($subtitle->language); ?> px-3 py-1 rounded text-sm transition">
                                <?php echo e($subtitle->label); ?>

                            </button>
                            <button onclick="downloadSubtitle('<?php echo e($subtitle->language); ?>')"
                                class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400" title="Download <?php echo e($subtitle->label); ?> subtitles">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </button>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div id="sub-settings-panel" class="hidden mt-2 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400 block mb-1">Font Size</label>
                            <div class="flex gap-1">
                                <button onclick="setSubSize('sm')" class="sub-size-btn px-2 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300" data-size="sm">A</button>
                                <button onclick="setSubSize('md')" class="sub-size-btn px-2 py-1 text-sm rounded bg-gray-200 dark:bg-gray-700 ring-2 ring-purple-500 text-gray-700 dark:text-gray-300" data-size="md">A</button>
                                <button onclick="setSubSize('lg')" class="sub-size-btn px-2 py-1 text-base rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300" data-size="lg">A</button>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-600 dark:text-gray-400 block mb-1">Background</label>
                            <div class="flex gap-1">
                                <button onclick="setSubBg('off')" class="sub-bg-btn px-2 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300" data-bg="off">Off</button>
                                <button onclick="setSubBg('semi')" class="sub-bg-btn px-2 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 ring-2 ring-purple-500 text-gray-700 dark:text-gray-300" data-bg="semi">Semi</button>
                                <button onclick="setSubBg('solid')" class="sub-bg-btn px-2 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300" data-bg="solid">Solid</button>
                            </div>
                        </div>
                    </div>
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
<div id="sub-toast"></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    #subtitle-hud {
        position: absolute;
        bottom: 64px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.75);
        color: #fff;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 13px;
        pointer-events: none;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.2s;
    }
    #subtitle-hud.show { opacity: 1; }
    #sub-toast {
        position: fixed;
        bottom: 80px;
        right: 24px;
        background: rgba(0,0,0,0.8);
        color: #fff;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }
    #sub-toast.show { opacity: 1; }
    .sub-btn {
        background: rgb(229 231 235);
        color: rgb(55 65 81);
    }
    .dark .sub-btn {
        background: rgb(31 41 55);
        color: rgb(209 213 219);
    }
    .sub-btn:hover {
        background: rgb(209 213 219);
    }
    .dark .sub-btn:hover {
        background: rgb(55 65 81);
    }
    .sub-btn.active {
        box-shadow: 0 0 0 2px rgb(168 85 247);
    }
</style>
<?php $__env->stopPush(); ?>

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
            'label' => $s->label,
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

        const LANG_LABELS = { 'en': 'English', 'ja': '日本語' };
        const STORAGE_KEY_SUB = 'animex_sub_lang';
        const STORAGE_KEY_SIZE = 'animex_sub_size';
        const STORAGE_KEY_BG = 'animex_sub_bg';

        let hls = null;
        const player = document.getElementById('video-player');
        const embedPlayer = document.getElementById('embed-player');
        const playOverlay = document.getElementById('play-overlay');

        let activeSubLang = null;
        let subtitleOffset = 0;
        let userSubChoice = false;
        let originalVttCache = {};

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
                            hls.loadSource(proxyUrl);
                            hls.on(Hls.Events.MANIFEST_PARSED, function () {
                                player.play().catch(function () {
                                    playOverlay.classList.remove('hidden');
                                });
                            });
                        } else if (['bufferStalledError', 'bufferSeekOverHole', 'bufferAppendError', 'fragParsingError'].includes(data.details)) {
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

        // ─── Subtitle System ────────────────────────────────────────────────

        function getSubUrl(sub) {
            return sub.proxy_path || sub.file_path;
        }

        function rebuildTracks() {
            if (!player) return;
            var existingTracks = player.querySelectorAll('track');
            existingTracks.forEach(function (t) { t.remove(); });

            subtitles.forEach(function (sub) {
                var track = document.createElement('track');
                track.kind = 'subtitles';
                track.srclang = sub.language;
                track.label = sub.label;

                if (sub.language === activeSubLang && subtitleOffset !== 0) {
                    var origUrl = getSubUrl(sub);
                    fetchAndAdjustVtt(origUrl, subtitleOffset, function (blobUrl) {
                        track.src = blobUrl;
                        track.addEventListener('load', function () {
                            if (player.textTracks) {
                                for (var i = 0; i < player.textTracks.length; i++) {
                                    var t = player.textTracks[i];
                                    t.mode = (t.language === activeSubLang) ? 'showing' : 'hidden';
                                }
                            }
                        });
                    });
                } else {
                    track.src = getSubUrl(sub);
                }

                track.default = sub.language === 'en' && !activeSubLang;
                player.appendChild(track);
            });

            if (activeSubLang && player.textTracks) {
                for (var i = 0; i < player.textTracks.length; i++) {
                    var t = player.textTracks[i];
                    t.mode = (t.language === activeSubLang) ? 'showing' : 'hidden';
                }
            }
        }

        function fetchAndAdjustVtt(url, offset, callback) {
            if (originalVttCache[url]) {
                callback(adjustVttBlob(originalVttCache[url], offset));
                return;
            }
            fetch(url).then(function (r) { return r.text(); }).then(function (vtt) {
                originalVttCache[url] = vtt;
                callback(adjustVttBlob(vtt, offset));
            }).catch(function () {
                callback(url);
            });
        }

        function adjustVttBlob(vtt, offset) {
            if (offset === 0) {
                var blob = new Blob([vtt], { type: 'text/vtt' });
                return URL.createObjectURL(blob);
            }
            var adjusted = vtt.replace(/(\d{2}):(\d{2}):(\d{2})\.(\d{3})/g, function (match, h, m, s, ms) {
                var totalMs = (parseInt(h) * 3600 + parseInt(m) * 60 + parseFloat(s)) * 1000 + parseInt(ms);
                totalMs += offset * 1000;
                if (totalMs < 0) totalMs = 0;
                var nh = String(Math.floor(totalMs / 3600000)).padStart(2, '0');
                var nm = String(Math.floor((totalMs % 3600000) / 60000)).padStart(2, '0');
                var ns = String(Math.floor((totalMs % 60000) / 1000)).padStart(2, '0');
                var nms = String(Math.floor(totalMs % 1000)).padStart(3, '0');
                return nh + ':' + nm + ':' + ns + '.' + nms;
            });
            var blob = new Blob([adjusted], { type: 'text/vtt' });
            return URL.createObjectURL(blob);
        }

        function selectSubtitle(lang) {
            userSubChoice = true;
            activeSubLang = lang;

            document.querySelectorAll('.sub-btn').forEach(function (btn) {
                btn.classList.remove('active', 'ring-2', 'ring-purple-500');
                btn.style.boxShadow = 'none';
            });

            if (lang) {
                var activeBtn = document.querySelector('.sub-' + lang);
                if (activeBtn) {
                    activeBtn.classList.add('active', 'ring-2', 'ring-purple-500');
                    activeBtn.style.boxShadow = '0 0 0 2px rgb(168 85 247)';
                }
                showSubToast('Subtitles: ' + (LANG_LABELS[lang] || lang.toUpperCase()));
                player.textTracks.forEach(function (track) {
                    track.mode = (track.language === lang) ? 'showing' : 'hidden';
                });
            } else {
                var offBtn = document.querySelector('.sub-off');
                if (offBtn) {
                    offBtn.classList.add('active', 'ring-2', 'ring-purple-500');
                    offBtn.style.boxShadow = '0 0 0 2px rgb(168 85 247)';
                }
                showSubToast('Subtitles: Off');
                player.textTracks.forEach(function (track) {
                    track.mode = 'disabled';
                });
            }

            localStorage.setItem(STORAGE_KEY_SUB, lang || '');
        }

        function selectSubtitleByLang(lang) {
            selectSubtitle(lang);
        }

        function cycleSubtitle() {
            var langs = subtitles.map(function (s) { return s.language; });
            var idx = activeSubLang ? langs.indexOf(activeSubLang) : -1;
            if (idx < 0 || idx >= langs.length - 1) {
                selectSubtitle(null);
            } else {
                selectSubtitle(langs[idx + 1]);
            }
        }

        function adjustOffset(delta) {
            subtitleOffset += delta;
            if (Math.abs(subtitleOffset) < 0.1) subtitleOffset = 0;
            var hud = document.getElementById('subtitle-hud');
            if (hud) {
                var sign = subtitleOffset >= 0 ? '+' : '';
                hud.textContent = 'Sub ' + sign + subtitleOffset.toFixed(1) + 's';
                hud.classList.add('show');
                clearTimeout(hud._hideTimer);
                hud._hideTimer = setTimeout(function () { hud.classList.remove('show'); }, 2000);
            }
            rebuildTracks();
            if (activeSubLang) {
                selectSubtitle(activeSubLang);
            }
        }

        function showSubToast(msg) {
            var toast = document.getElementById('sub-toast');
            if (!toast) return;
            toast.textContent = msg;
            toast.classList.add('show');
            clearTimeout(toast._hideTimer);
            toast._hideTimer = setTimeout(function () { toast.classList.remove('show'); }, 2000);
        }

        function downloadSubtitle(lang) {
            var sub = subtitles.find(function (s) { return s.language === lang; });
            if (!sub) return;
            var url = getSubUrl(sub);
            fetch(url).then(function (r) { return r.blob(); }).then(function (blob) {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'subtitle_' + lang + '.vtt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(a.href);
            }).catch(function () { });
        }

        // ─── Subtitle Settings ──────────────────────────────────────────────

        function toggleSubSettings() {
            var panel = document.getElementById('sub-settings-panel');
            if (panel) panel.classList.toggle('hidden');
        }

        function setSubSize(size) {
            ['sm', 'md', 'lg'].forEach(function (s) {
                player.classList.remove('subtitle-' + s);
            });
            if (size !== 'md') player.classList.add('subtitle-' + size);
            document.querySelectorAll('.sub-size-btn').forEach(function (btn) {
                btn.classList.remove('ring-2', 'ring-purple-500');
                if (btn.dataset.size === size) btn.classList.add('ring-2', 'ring-purple-500');
            });
            localStorage.setItem(STORAGE_KEY_SIZE, size);
        }

        function setSubBg(bg) {
            ['off', 'semi', 'solid'].forEach(function (b) {
                player.classList.remove('subtitle-bg-' + b);
            });
            if (bg !== 'semi') player.classList.add('subtitle-bg-' + bg);
            document.querySelectorAll('.sub-bg-btn').forEach(function (btn) {
                btn.classList.remove('ring-2', 'ring-purple-500');
                if (btn.dataset.bg === bg) btn.classList.add('ring-2', 'ring-purple-500');
            });
            localStorage.setItem(STORAGE_KEY_BG, bg);
        }

        function loadSubSettings() {
            var size = localStorage.getItem(STORAGE_KEY_SIZE) || 'md';
            var bg = localStorage.getItem(STORAGE_KEY_BG) || 'semi';
            setSubSize(size);
            setSubBg(bg);
        }

        // ─── Initialization ────────────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', function () {
            var savedLang = localStorage.getItem(STORAGE_KEY_SUB);
            if (savedLang) {
                var found = subtitles.some(function (s) { return s.language === savedLang; });
                if (found) activeSubLang = savedLang;
            }
            loadSubSettings();
            if (sources.length > 0) loadSource(0);
            setTimeout(function () {
                if (activeSubLang) {
                    selectSubtitle(activeSubLang);
                } else if (subtitles.length > 0) {
                    var enSub = subtitles.find(function (s) { return s.language === 'en'; });
                    if (enSub) {
                        activeSubLang = 'en';
                        selectSubtitle('en');
                    }
                }
            }, 1000);
        });

        // ─── Keyboard Shortcuts ────────────────────────────────────────────

        document.addEventListener('keydown', function (e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (embedPlayer && !embedPlayer.classList.contains('hidden')) return;

            switch (e.key) {
                case 's':
                case 'S':
                    e.preventDefault();
                    cycleSubtitle();
                    break;
                case '[':
                    e.preventDefault();
                    adjustOffset(-0.5);
                    break;
                case ']':
                    e.preventDefault();
                    adjustOffset(0.5);
                    break;
            }
        });

        // ─── Video Events ──────────────────────────────────────────────────

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
                if (!userSubChoice && !activeSubLang && subtitles.length > 0) {
                    var enSub = subtitles.find(function (s) { return s.language === 'en'; });
                    if (enSub) {
                        activeSubLang = 'en';
                        setTimeout(function () { selectSubtitle('en'); }, 500);
                    }
                } else if (activeSubLang) {
                    setTimeout(function () { selectSubtitle(activeSubLang); }, 500);
                }
            });
            player.textTracks.addEventListener('addtrack', function () {
                if (!userSubChoice && activeSubLang) {
                    for (var i = 0; i < player.textTracks.length; i++) {
                        var t = player.textTracks[i];
                        t.mode = (t.language === activeSubLang) ? 'showing' : 'hidden';
                    }
                }
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