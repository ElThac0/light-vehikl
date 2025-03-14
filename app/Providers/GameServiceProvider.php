<?php

namespace App\Providers;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Facades\Octane;

class GameServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Octane::tick('game-state', function () {
            $gameList = Cache::get('game_list');

            if (!$gameList) {
                return;
            }

            collect($gameList)->each(function ($gameId) use ($gameList) {
                $game = GameState::find($gameId);
                $game->nextTick();
                $game->save();

                if ($game->isOver()) {
                    logger()->warning("Game {$gameId} is over");
                    $gameList = array_filter($gameList, function ($item) use ($gameId) {
                        return $item !== $gameId;
                    });
                    Cache::set('game_list', $gameList);
                }
            });
        })->seconds(1);
    }
}
