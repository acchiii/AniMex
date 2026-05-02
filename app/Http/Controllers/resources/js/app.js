import './bootstrap';

// ─── Alpine.js ────────────────────────────────────────────────────────────────
import Alpine from 'alpinejs';
import Persist  from '@alpinejs/persist';
import Focus    from '@alpinejs/focus';
import Collapse from '@alpinejs/collapse';
import Intersect from '@alpinejs/intersect';

Alpine.plugin(Persist);
Alpine.plugin(Focus);
Alpine.plugin(Collapse);
Alpine.plugin(Intersect);

// ─── Dark Mode Store ─────────────────────────────────────────────────────────
Alpine.store('theme', {
    value: Alpine.$persist('system').as('animex_theme'),

    get isDark() {
        if (this.value === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return this.value === 'dark';
    },

    init() {
        this.apply();
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (this.value === 'system') this.apply();
        });
    },

    apply() {
        if (this.isDark) {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        }
    },

    set(val) {
        this.value = val;
        this.apply();
    },

    toggle() {
        this.set(this.isDark ? 'light' : 'dark');
    },
});

// ─── Search Component ────────────────────────────────────────────────────────
Alpine.data('searchModal', () => ({
    open: false,
    query: '',
    results: [],
    loading: false,
    debounceTimer: null,

    init() {
        this.$watch('query', val => {
            clearTimeout(this.debounceTimer);
            if (val.length < 2) { this.results = []; return; }
            this.debounceTimer = setTimeout(() => this.search(), 300);
        });

        document.addEventListener('keydown', e => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.open = !this.open;
            }
            if (e.key === 'Escape') this.close();
        });
    },

    openModal() { this.open = true; this.$nextTick(() => this.$refs.input?.focus()); },
    close()     { this.open = false; this.query = ''; this.results = []; },

    async search() {
        if (this.query.length < 2) return;
        this.loading = true;
        try {
            const res = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            this.results = data.results || [];
        } catch (e) {
            console.error('Search failed', e);
        } finally {
            this.loading = false;
        }
    },
}));

// ─── Notification Bell ────────────────────────────────────────────────────────
Alpine.data('notifications', () => ({
    open: false,
    unreadCount: 0,
    items: [],

    async init() {
        if (!window._auth) return;
        await this.fetchCount();
    },

    async fetchCount() {
        try {
            const res  = await fetch('/api/notifications/count');
            const data = await res.json();
            this.unreadCount = data.count || 0;
        } catch {}
    },

    async open_dropdown() {
        this.open = true;
        if (!this.items.length) await this.fetchNotifications();
    },

    async fetchNotifications() {
        try {
            const res  = await fetch('/api/notifications');
            const data = await res.json();
            this.items = data.notifications || [];
        } catch {}
    },

    async markAllRead() {
        await fetch('/api/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': window._csrf } });
        this.unreadCount = 0;
        this.items = this.items.map(n => ({ ...n, read_at: new Date().toISOString() }));
    },
}));

// ─── Lazy Image Loading ───────────────────────────────────────────────────────
Alpine.data('lazyImage', (src) => ({
    loaded: false,
    error:  false,
    src,

    init() {
        const img = new Image();
        img.onload  = () => { this.loaded = true; };
        img.onerror = () => { this.error = true; };
        img.src = this.src;
    },
}));

// ─── Watchlist Toggle ─────────────────────────────────────────────────────────
Alpine.data('watchlist', (animeId, initialType) => ({
    type: initialType,
    loading: false,

    async toggle(newType) {
        if (!window._auth) { window.location.href = '/login'; return; }
        this.loading = true;
        try {
            const res = await fetch(`/api/favorites/${animeId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window._csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ type: this.type === newType ? null : newType }),
            });
            const data = await res.json();
            this.type = data.type;
        } finally {
            this.loading = false;
        }
    },

    get isFavorited() { return !!this.type; },
}));

// ─── Rating ──────────────────────────────────────────────────────────────────
Alpine.data('ratingWidget', (animeId, initialScore) => ({
    score: initialScore || 0,
    hovered: 0,
    loading: false,

    async rate(score) {
        if (!window._auth) { window.location.href = '/login'; return; }
        this.loading = true;
        this.score   = score;
        try {
            await fetch(`/api/ratings/${animeId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window._csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ score }),
            });
        } finally {
            this.loading = false;
        }
    },
}));

// ─── Toast Notifications ──────────────────────────────────────────────────────
Alpine.data('toastManager', () => ({
    toasts: [],
    nextId: 0,

    add(message, type = 'info', duration = 4000) {
        const id = this.nextId++;
        this.toasts.push({ id, message, type });
        if (duration > 0) setTimeout(() => this.remove(id), duration);
    },

    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    },

    success(msg) { this.add(msg, 'success'); },
    error(msg)   { this.add(msg, 'error'); },
    info(msg)    { this.add(msg, 'info'); },
}));

// ─── Video Player ─────────────────────────────────────────────────────────────
import Hls   from 'hls.js';
import Plyr  from 'plyr';

window.initPlayer = function(options) {
    const {
        videoEl,
        src,
        type,
        subtitles = [],
        poster = '',
        animeId,
        episodeId,
        startTime = 0,
        nextEpisodeUrl = null,
        introStart,
        introEnd,
        autoNext = true,
    } = options;

    let hls;

    if (type === 'hls' && Hls.isSupported() && src.includes('.m3u8')) {
        hls = new Hls({
            enableWorker: true,
            lowLatencyMode: false,
            backBufferLength: 90,
        });
        hls.loadSource(src);
        hls.attachMedia(videoEl);
    } else if (videoEl.canPlayType('application/vnd.apple.mpegurl') && src.includes('.m3u8')) {
        videoEl.src = src;
    } else {
        videoEl.src = src;
    }

    const player = new Plyr(videoEl, {
        controls: [
            'play-large','rewind','play','fast-forward','progress',
            'current-time','duration','mute','volume','captions',
            'settings','pip','airplay','fullscreen',
        ],
        settings: ['captions', 'quality', 'speed', 'loop'],
        speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
        keyboard: { focused: true, global: true },
        tooltips: { controls: true, seek: true },
        captions: { active: true, language: 'auto', update: true },
        poster,
        autoplay: false,
    });

    // Add subtitles
    subtitles.forEach(sub => {
        const track = document.createElement('track');
        track.kind    = 'subtitles';
        track.label   = sub.label;
        track.srclang = sub.language;
        track.src     = sub.url;
        if (sub.is_default) track.default = true;
        videoEl.appendChild(track);
    });

    // Resume from last position
    player.on('ready', () => {
        if (startTime > 10) {
            player.currentTime = startTime - 5;
        }
    });

    // Save progress
    let progressTimer;
    player.on('timeupdate', () => {
        clearTimeout(progressTimer);
        progressTimer = setTimeout(() => {
            if (episodeId && player.playing) {
                fetch(`/episodes/${episodeId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({
                        progress: Math.floor(player.currentTime),
                        duration: Math.floor(player.duration),
                    }),
                }).catch(() => {});
            }
        }, 5000);
    });

    // Skip intro button
    const skipIntroBtn = document.getElementById('skip-intro-btn');
    if (skipIntroBtn && introStart !== undefined) {
        player.on('timeupdate', () => {
            const t = player.currentTime;
            if (t >= introStart && t <= introEnd) {
                skipIntroBtn.classList.remove('hidden');
            } else {
                skipIntroBtn.classList.add('hidden');
            }
        });

        skipIntroBtn.addEventListener('click', () => {
            player.currentTime = introEnd;
        });
    }

    // Auto next episode
    if (autoNext && nextEpisodeUrl) {
        const countdownEl = document.getElementById('next-ep-countdown');
        player.on('ended', () => {
            if (countdownEl) {
                let sec = 5;
                countdownEl.classList.remove('hidden');
                const interval = setInterval(() => {
                    sec--;
                    const numEl = document.getElementById('next-ep-sec');
                    if (numEl) numEl.textContent = sec;
                    if (sec <= 0) {
                        clearInterval(interval);
                        window.location.href = nextEpisodeUrl;
                    }
                }, 1000);
                document.getElementById('cancel-next-ep')?.addEventListener('click', () => {
                    clearInterval(interval);
                    countdownEl.classList.add('hidden');
                });
            } else {
                setTimeout(() => window.location.href = nextEpisodeUrl, 3000);
            }
        });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', e => {
        if (['INPUT','TEXTAREA','SELECT'].includes(e.target.tagName)) return;
        switch(e.key) {
            case ' ':
            case 'k': e.preventDefault(); player.togglePlay(); break;
            case 'ArrowRight': e.preventDefault(); player.currentTime += 10; break;
            case 'ArrowLeft':  e.preventDefault(); player.currentTime -= 10; break;
            case 'ArrowUp':    e.preventDefault(); player.volume = Math.min(1, player.volume + 0.1); break;
            case 'ArrowDown':  e.preventDefault(); player.volume = Math.max(0, player.volume - 0.1); break;
            case 'f': player.fullscreen.toggle(); break;
            case 'm': player.muted = !player.muted; break;
        }
    });

    window._player = player;
    return player;
};

// ─── Swiper Carousels ─────────────────────────────────────────────────────────
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';

Swiper.use([Navigation, Pagination, Autoplay, EffectFade]);
window.Swiper = Swiper;

// ─── Initialize Alpine ────────────────────────────────────────────────────────
window.Alpine = Alpine;
Alpine.start();

// ─── Theme init on load ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Apply saved theme before Alpine loads (prevent flash)
    const savedTheme = localStorage.getItem('animex_theme')?.replace(/"/g, '') || 'system';
    const isDark = savedTheme === 'dark' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.add('light');
    }
});