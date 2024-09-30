<?php

use App\Http\Controllers\AddPlayer;
use App\Http\Controllers\CreateGame;
use App\Http\Controllers\GetGame;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $gameList = Cache::remember('game_list', 3600, function () {
        return [];
    });
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'sessionId' => session()->id(),
        'gameList' => $gameList,
    ]);
});

//Route::get('/game', [GameController::class, 'index'])->name('game.index');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/games', CreateGame::class)->name('game.create');
Route::get('/my-game', GetGame::class)->name('game.my');

require __DIR__.'/auth.php';
