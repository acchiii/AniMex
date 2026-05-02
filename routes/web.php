<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AnimeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAnimeController;

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

// API-like AJAX Routes
Route::post('/anime/{animeId}/rate', [AnimeController::class, 'rate'])->name('anime.rate');
Route::post('/anime/{animeId}/favorite', [AnimeController::class, 'favorite'])->name('anime.favorite');
Route::post('/anime/{animeId}/comment', [AnimeController::class, 'postComment'])->name('anime.comment');

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

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
});
