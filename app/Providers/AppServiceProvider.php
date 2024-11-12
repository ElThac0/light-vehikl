<?php

namespace App\Providers;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Facades\Octane;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Octane::tick("game-state", function () {
            $gameList = Cache::get('game_list');

            if (! $gameList) {
                return;
            }

            collect($gameList)->each(function ($gameId) {
                $game = GameState::find($gameId);
                $game->nextTick();
                $game->save();
            });
        })
            ->seconds(1);
    }
}
