<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AnimeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;


Route::get('/', [HomeController::class, 'index'])->name('home');

// Anime Routes
Route::get('/anime', [AnimeController::class, 'index'])->name('anime.index');
Route::get('/anime/{slug}', [AnimeController::class, 'show'])->name('anime.show');
Route::get('/anime/{slug}/watch/{episodeNumber}', [AnimeController::class, 'stream'])->name('anime.stream');

// Browse Routes
Route::get('/popular', [AnimeController::class, 'popular'])->name('anime.popular');
Route::get('/schedule', [AnimeController::class, 'schedule'])->name('anime.schedule');
Route::get('/genre/{slug}', [AnimeController::class, 'genre'])->name('anime.genre');
Route::get('/studio/{slug}', [AnimeController::class, 'studio'])->name('anime.studio');
Route::get('/recent', [AnimeController::class, 'recentlyAdded'])->name('anime.recent');

// Auth Routes
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAnimeController;

// Profile Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/email/verification-notification', [ProfileController::class, 'resendVerification'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin login overlay

    Route::get('/login', function () {
        return redirect()->route('admin.dashboard');
    })->name('login');

    Route::post('/login', [\App\Http\Controllers\Admin\AdminLoginController::class, 'login'])->name('login.post');

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard')->middleware('admin');


    Route::get('/anime', [AdminAnimeController::class, 'index'])->name('anime.index');
    Route::get('/anime/create', [AdminAnimeController::class, 'create'])->name('anime.create');
    Route::post('/anime', [AdminAnimeController::class, 'store'])->name('anime.store');
    Route::get('/anime/{anime}/edit', [AdminAnimeController::class, 'edit'])->name('anime.edit');
    Route::put('/anime/{anime}', [AdminAnimeController::class, 'update'])->name('anime.update');
    Route::delete('/anime/{anime}', [AdminAnimeController::class, 'destroy'])->name('anime.destroy');

    Route::get('/anime/{anime}/episodes', [AdminAnimeController::class, 'episodes'])->name('anime.episodes');
    Route::post('/anime/{anime}/episodes', [AdminAnimeController::class, 'storeEpisode'])->name('anime.episodes.store');
    Route::put('/anime/{anime}/episodes/{episode}', [AdminAnimeController::class, 'updateEpisode'])->name('anime.episodes.update');
    Route::delete('/anime/{anime}/episodes/{episode}', [AdminAnimeController::class, 'destroyEpisode'])->name('anime.episodes.destroy');

    Route::get('/import', [\App\Http\Controllers\Admin\AdminJikanController::class, 'search'])->name('import.search');
    Route::post('/import/{malId}', [\App\Http\Controllers\Admin\AdminJikanController::class, 'import'])->name('import.anime');

    Route::post('/anime/{anime}/episodes/{episode}/import-sources', [\App\Http\Controllers\Admin\AdminAniAPIController::class, 'importEpisodeSources'])->name('anime.episodes.import-sources');
    Route::post('/anime/{anime}/import-all-sources', [\App\Http\Controllers\Admin\AdminAniAPIController::class, 'importAllEpisodeSources'])->name('anime.import-all-sources');
    Route::post('/anime/{anime}/fetch-episode-list', [\App\Http\Controllers\Admin\AdminAniAPIController::class, 'fetchEpisodeList'])->name('anime.fetch-episode-list');
    Route::post('/anime/{anime}/episodes/{episode}/sources', [\App\Http\Controllers\Admin\AdminAnimeController::class, 'storeSource'])->name('anime.episodes.sources.store');
    Route::delete('/anime/{anime}/episodes/{episode}/sources/{source}', [\App\Http\Controllers\Admin\AdminAnimeController::class, 'destroySource'])->name('anime.episodes.sources.destroy');
});

// API-like AJAX Routes
Route::post('/anime/{animeId}/rate', [AnimeController::class, 'rate'])->name('anime.rate');
Route::post('/anime/{animeId}/favorite', [AnimeController::class, 'favorite'])->name('anime.favorite');
Route::post('/anime/{animeId}/comment', [AnimeController::class, 'postComment'])->name('anime.comment');
Route::get('/proxy/source/{source}', [AnimeController::class, 'proxySource'])->name('proxy.source');
Route::get('/proxy/segment/{sourceId}', [AnimeController::class, 'proxySegment'])->name('proxy.segment');
Route::post('/progress', [AnimeController::class, 'saveProgress'])->name('progress.save');
Route::get('/proxy/subtitle', [AnimeController::class, 'proxySubtitle'])->name('proxy.subtitle');
Route::get('/subtitles/{filename}', [AnimeController::class, 'serveSubtitle'])->name('subtitle.serve');


