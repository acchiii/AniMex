

<?php $__env->startSection('content'); ?>

<?php $__env->startPush('styles'); ?>
<style>
  :root { --h-start: #f9fafb; --h-mid: rgba(249,250,251,0.7); --h-top: rgba(249,250,251,0.25); }
  .dark { --h-start: #0a0a0f; --h-mid: rgba(10,10,15,0.7); --h-top: rgba(10,10,15,0.25); }
  .hero-overlay { background: linear-gradient(to top, var(--h-start) 0%, var(--h-mid) 40%, var(--h-top) 70%, transparent 100%); }
  .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
<?php $__env->stopPush(); ?>


<div class="relative h-[70vh] overflow-hidden">
    <div class="absolute inset-0" id="hero-bg"></div>
    <div class="absolute inset-0 hero-overlay"></div>
    <div class="relative h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-end pb-20">
        <div class="max-w-2xl" id="hero-content">
            <?php if($heroAnime->isNotEmpty()): ?>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white leading-tight"><?php echo e($heroAnime->first()->title_english ?? $heroAnime->first()->title); ?></h1>
                <p class="text-gray-600 dark:text-gray-400 mb-6 line-clamp-3 text-sm leading-relaxed"><?php echo e($heroAnime->first()->synopsis); ?></p>
                <div class="flex items-center gap-3">
                    <a href="<?php echo e(url('/anime/' . $heroAnime->first()->slug)); ?>" class="hero-watch bg-purple-600 hover:bg-purple-700 px-5 py-2.5 rounded-lg text-sm font-medium text-white transition flex items-center gap-2 shadow-sm shadow-purple-600/25">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
                        Watch Now
                    </a>
                    <a href="<?php echo e(url('/anime/' . $heroAnime->first()->slug)); ?>" class="hero-details bg-gray-200/80 dark:bg-white/10 hover:bg-gray-300/80 dark:hover:bg-white/15 text-gray-900 dark:text-white px-5 py-2.5 rounded-lg text-sm font-medium transition">Details</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if($heroAnime->count() > 1): ?>
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-1.5" id="hero-dots">
        <?php $__currentLoopData = $heroAnime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button onclick="setHeroSlide(<?php echo e($i); ?>)" class="hero-dot w-1.5 h-1.5 rounded-full transition-all <?php echo e($i === 0 ? 'bg-purple-500 w-5' : 'bg-gray-400/60 dark:bg-white/30 hover:bg-gray-400 dark:hover:bg-white/50'); ?>"></button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">

    <?php if($trending->isNotEmpty()): ?>
    <section>
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Trending</h2>
            <a href="<?php echo e(route('anime.popular')); ?>" class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition">View All &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $trending->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                        <?php if($anime->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" loading="lazy" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <?php if($anime->is_trending): ?>
                            <span class="absolute top-2 left-2 bg-purple-600/90 backdrop-blur-sm text-white text-[11px] px-2 py-0.5 rounded-md font-semibold">#<?php echo e($loop->iteration); ?></span>
                        <?php endif; ?>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                            <h3 class="text-sm font-medium text-white truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if($topRated->isNotEmpty()): ?>
    <section>
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Top Rated</h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $topRated->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                        <?php if($anime->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" loading="lazy" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="absolute top-2 right-2 bg-amber-400/90 backdrop-blur-sm text-amber-900 text-[11px] px-1.5 py-0.5 rounded-md font-semibold"><?php echo e(number_format($anime->score, 1)); ?></span>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                            <h3 class="text-sm font-medium text-white truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if($genreSections): ?>
    <?php $__currentLoopData = $genreSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $genreName => $genreAnime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($genreAnime->isNotEmpty()): ?>
        <section>
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo e($genreName); ?></h2>
                <a href="<?php echo e(route('anime.genre', $genreName)); ?>" class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition">View All &rarr;</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php $__currentLoopData = $genreAnime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                        <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                            <?php if($anime->cover_image): ?>
                                <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" loading="lazy" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                            <?php endif; ?>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                                <h3 class="text-sm font-medium text-white truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                            </div>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

    <?php if($upcoming->isNotEmpty()): ?>
    <section>
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Upcoming</h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $upcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm dark:shadow-none ring-1 ring-gray-200/60 dark:ring-gray-700/40 group-hover:ring-purple-400/50 dark:group-hover:ring-purple-500/50 transition">
                        <?php if($anime->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300" loading="lazy" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600 text-4xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="absolute top-2 left-2 bg-emerald-500/90 backdrop-blur-sm text-white text-[11px] px-2 py-0.5 rounded-md font-semibold">Upcoming</span>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3 pt-12">
                            <h3 class="text-sm font-medium text-white truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php $__env->startPush('scripts'); ?>
<script>
const heroData = <?php echo json_encode($heroJson, 15, 512) ?>;
let currentSlide = 0;
let heroInterval;

function setHeroSlide(index) {
    currentSlide = index;
    const item = heroData[index];
    const bg = document.getElementById('hero-bg');
    const content = document.getElementById('hero-content');
    const dots = document.querySelectorAll('.hero-dot');

    if (bg) {
        const imgUrl = item.cover_image?.startsWith('http') ? item.cover_image : `/storage/${item.cover_image}`;
        bg.style.transition = 'opacity 0.6s';
        bg.style.opacity = '0';
        setTimeout(() => {
            bg.style.backgroundImage = `url(${imgUrl})`;
            bg.style.backgroundSize = 'cover';
            bg.style.backgroundPosition = 'center';
            bg.style.opacity = '1';
        }, 300);
    }

    if (content) {
        content.style.transition = 'opacity 0.6s';
        content.style.opacity = '0';
        setTimeout(() => {
            const watchBtn = content.querySelector('.hero-watch');
            const detailsBtn = content.querySelector('.hero-details');
            content.querySelector('h1').textContent = item.title;
            content.querySelector('p').textContent = item.synopsis;
            if (watchBtn) watchBtn.href = `/anime/${item.slug}`;
            if (detailsBtn) detailsBtn.href = `/anime/${item.slug}`;
            content.style.opacity = '1';
        }, 300);
    }

    dots.forEach((dot, i) => {
        dot.className = i === index
            ? 'hero-dot w-1.5 h-1.5 rounded-full transition-all bg-purple-500 w-5'
            : 'hero-dot w-1.5 h-1.5 rounded-full transition-all bg-gray-400/60 dark:bg-white/30 hover:bg-gray-400 dark:hover:bg-white/50';
    });

    resetInterval();
}

function nextSlide() { setHeroSlide((currentSlide + 1) % heroData.length); }
function resetInterval() { clearInterval(heroInterval); heroInterval = setInterval(nextSlide, 5000); }

if (heroData.length > 0) {
    const bg = document.getElementById('hero-bg');
    const first = heroData[0];
    const imgUrl = first.cover_image?.startsWith('http') ? first.cover_image : `/storage/${first.cover_image}`;
    if (bg) {
        bg.style.backgroundImage = `url(${imgUrl})`;
        bg.style.backgroundSize = 'cover';
        bg.style.backgroundPosition = 'center';
    }
    resetInterval();
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/home.blade.php ENDPATH**/ ?>