<?php $__env->startSection('content'); ?>

<?php $__env->startPush('styles'); ?>
<style>
.hero-gradient { background: linear-gradient(to top, #030712 0%, rgba(3,7,18,0.8) 30%, rgba(3,7,18,0.4) 60%, transparent 100%); }
</style>
<?php $__env->stopPush(); ?>


<div class="relative h-[70vh] overflow-hidden">
    <div class="absolute inset-0" id="hero-bg"></div>
    <div class="absolute inset-0 hero-gradient"></div>
    <div class="relative h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-end pb-20">
        <div class="max-w-2xl" id="hero-content">
            <?php if($heroAnime->isNotEmpty()): ?>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-white"><?php echo e($heroAnime->first()->title_english ?? $heroAnime->first()->title); ?></h1>
                <p class="text-gray-300 mb-6 line-clamp-3"><?php echo e($heroAnime->first()->synopsis); ?></p>
                <div class="flex items-center gap-4">
                    <a href="<?php echo e(url('/anime/' . $heroAnime->first()->slug)); ?>" class="hero-watch bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                        Watch Now
                    </a>
                    <a href="<?php echo e(url('/anime/' . $heroAnime->first()->slug)); ?>" class="hero-details bg-gray-800 hover:bg-gray-700 px-6 py-3 rounded-lg font-medium transition">Details</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if($heroAnime->count() > 1): ?>
    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2" id="hero-dots">
        <?php $__currentLoopData = $heroAnime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button onclick="setHeroSlide(<?php echo e($i); ?>)" class="hero-dot w-2 h-2 rounded-full transition <?php echo e($i === 0 ? 'bg-purple-500 w-6' : 'bg-white/40'); ?>"></button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">

    <?php if($trending->isNotEmpty()): ?>
    
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold">🔥 Trending</h2>
            <a href="<?php echo e(route('anime.popular')); ?>" class="text-purple-400 hover:text-purple-300 text-sm">View All →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $trending->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        <?php if($anime->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <?php if($anime->is_trending): ?>
                            <span class="absolute top-2 left-2 bg-purple-600 text-white text-xs px-2 py-1 rounded font-medium">#<?php echo e($loop->iteration); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if($topRated->isNotEmpty()): ?>
    
    <section>
        <h2 class="text-2xl font-bold mb-4">⭐ Top Rated</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $topRated->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        <?php if($anime->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="absolute top-2 right-2 bg-yellow-500 text-black text-xs px-2 py-1 rounded font-bold"><?php echo e(number_format($anime->score, 1)); ?></span>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if($genreSections): ?>
    
    <?php $__currentLoopData = $genreSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $genreName => $genreAnime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($genreAnime->isNotEmpty()): ?>
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold"><?php echo e($genreName); ?></h2>
                <a href="<?php echo e(route('anime.genre', $genreName)); ?>" class="text-purple-400 hover:text-purple-300 text-sm">View All →</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php $__currentLoopData = $genreAnime; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                        <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                            <?php if($anime->cover_image): ?>
                                <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

    <?php if($upcoming->isNotEmpty()): ?>
    
    <section>
        <h2 class="text-2xl font-bold mb-4">📅 Upcoming</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $upcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $anime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(url('/anime/' . $anime->slug)); ?>" class="group">
                    <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-gray-800">
                        <?php if($anime->cover_image): ?>
                            <img src="<?php echo e(str_starts_with($anime->cover_image, 'http') ? $anime->cover_image : asset('storage/' . $anime->cover_image)); ?>" alt="<?php echo e($anime->title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-600 text-3xl font-bold"><?php echo e(substr($anime->title, 0, 1)); ?></div>
                        <?php endif; ?>
                        <span class="absolute top-2 left-2 bg-yellow-600 text-white text-xs px-2 py-1 rounded">Upcoming</span>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-white group-hover:text-purple-400 truncate"><?php echo e($anime->title_english ?? $anime->title); ?></h3>
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
        bg.style.transition = 'opacity 0.8s';
        bg.style.opacity = '0';
        setTimeout(() => {
            bg.style.backgroundImage = `url(${imgUrl})`;
            bg.style.backgroundSize = 'cover';
            bg.style.backgroundPosition = 'center';
            bg.style.opacity = '1';
        }, 400);
    }

    if (content) {
        content.style.transition = 'opacity 0.8s';
        content.style.opacity = '0';
        setTimeout(() => {
            const watchBtn = content.querySelector('.hero-watch');
            const detailsBtn = content.querySelector('.hero-details');
            content.querySelector('h1').textContent = item.title;
            content.querySelector('p').textContent = item.synopsis;
            if (watchBtn) watchBtn.href = `/anime/${item.slug}`;
            if (detailsBtn) detailsBtn.href = `/anime/${item.slug}`;
            content.style.opacity = '1';
        }, 400);
    }

    dots.forEach((dot, i) => {
        dot.className = i === index ? 'hero-dot w-2 h-2 rounded-full transition bg-purple-500 w-6' : 'hero-dot w-2 h-2 rounded-full transition bg-white/40';
    });

    resetInterval();
}

function nextSlide() {
    setHeroSlide((currentSlide + 1) % heroData.length);
}

function resetInterval() {
    clearInterval(heroInterval);
    heroInterval = setInterval(nextSlide, 5000);
}

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