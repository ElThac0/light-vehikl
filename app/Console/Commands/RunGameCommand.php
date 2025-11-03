<?php

namespace App\Console\Commands;

use Vehikl\LvObjects\Enums\GameStatus;
use App\GameObjects\GameState;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RunGameCommand extends Command
{
    protected $signature = 'run:game {gameId}';

    protected $description = 'Command description';

    protected int $sleepTime = 5000;

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        $game = GameState::find($gameId);
        $game->setStatus(GameStatus::ACTIVE);
        $this->info('Running game...');

        while (!$game->isOver()) {
            $game->nextTick();
            $game->save();
            Sleep::for(200)->milliseconds();
            $game = GameState::find($gameId);
        }

        try {
            $gameList = cache()->get('game_list');
            $gameList = array_diff($gameList, [$gameId]);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            $gameList = [];
        }

        cache()->put('game_list', $gameList);

        $this->info('Done.');
    }
}
