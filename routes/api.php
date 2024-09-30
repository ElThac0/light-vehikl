<?php

use App\Http\Controllers\AddPlayer;
use App\Http\Controllers\CreateGame;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/player-joined/{id}', AddPlayer::class)->name('player.joined');

Route::post('/games', CreateGame::class)->name('game.create');
