<?php

namespace App\Console\Commands;

use App\Exceptions\GameNotFound;
use App\GameObjects\GameState;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use LightVehikl\LvObjects\Enums\GameStatus;
use LightVehikl\LvObjects\Enums\PlayerStatus;
use LightVehikl\LvObjects\GameObjects\Player;

class RunGameCommand extends Command
{
    protected $signature = 'run:game {gameId}';

    protected $description = 'Command description';

    protected int $sleepTime = 5000;

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        try {
            GameState::mutate($gameId, function (GameState $game) {
                $game->setStatus(GameStatus::ACTIVE);
                $game->getPlayers()->each(fn (Player $player) => $player->setStatus(PlayerStatus::ACTIVE));
            });

            $this->info('Running game...');

            $over = false;

            while (! $over) {
                $over = GameState::mutate($gameId, function (GameState $game) {
                    $game->nextTick();

                    return $game->isOver();
                });

                if (! $over) {
                    Sleep::for(200)->milliseconds();
                }
            }
        } catch (GameNotFound $e) {
            $this->error($e->getMessage());

            return;
        }

        GameState::forget($gameId);

        $this->info('Done.');
    }
}
