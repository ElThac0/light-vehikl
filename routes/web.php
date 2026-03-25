<?php

use App\GameObjects\GameState;
use App\Http\Controllers\AddBot;
use App\Http\Controllers\GameList;
use App\Http\Controllers\JoinGame;
use App\Http\Controllers\CreateGame;
use App\Http\Controllers\GameMove;
use App\Http\Controllers\GetGame;
use App\Http\Controllers\LeaveGame;
use App\Http\Controllers\MarkReady;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StartGame;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'sessionId' => session()->id(),
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::withoutMiddleware([ValidateCsrfToken::class])->group(function () {
    Route::post('/games', CreateGame::class)->name('game.create');
    Route::get('/games', GameList::class)->name('game.list');
    Route::post('/game/{id}/start', StartGame::class)->name('game.start');
    Route::get('/my-game', GetGame::class)->name('game.my');
    Route::post('/game/{id}/move', GameMove::class)->name('game.move');

    Route::post('/join-game/{id}', JoinGame::class)->name('game.join')->withoutMiddleware([ValidateCsrfToken::class]);
    Route::post('/add-bot/{id}', AddBot::class)->name('game.add-bot');
    Route::post('/leave-game/{id}', LeaveGame::class)->name('game.leave');
    Route::post('/mark-ready/{id}', MarkReady::class)->name('game.mark-ready');
});


require __DIR__.'/auth.php';
