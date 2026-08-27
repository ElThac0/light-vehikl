<?php

namespace App\Providers;

use App\Exceptions\GameNotFound;
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

            if (! $gameList) {
                return;
            }

            collect($gameList)->each(function ($gameId) {
                try {
                    $over = GameState::mutate($gameId, function (GameState $game) {
                        $game->nextTick();

                        return $game->isOver();
                    });
                } catch (GameNotFound) {
                    $over = true;
                }

                if ($over) {
                    logger()->warning("Game {$gameId} is over");
                    GameState::forget($gameId);
                }
            });
        })->seconds(1);
    }
}
