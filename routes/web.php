<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AnimeController;
use App\Http\Controllers\HomeController;

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
