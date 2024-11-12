<?php

use App\GameObjects\GameState;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    $gameList = Cache::get('game_list');

    if (! $gameList) {
        return;
    }

    collect($gameList)->each(function ($gameId) {
        $game = GameState::find($gameId);
        $game->nextTick();
        $game->save();
    });
})->everySecond();
