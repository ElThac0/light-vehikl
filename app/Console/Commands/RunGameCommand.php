<?php

namespace App\Console\Commands;

use App\GameObjects\GameState;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

class RunGameCommand extends Command
{
    protected $signature = 'run:game {gameId}';

    protected $description = 'Command description';

    protected int $sleepTime = 5000;

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        $game = GameState::find($gameId);
        $this->info('Running game...');

        while (!$game->isOver()) {
            $game->nextTick();
            $game->save();
            Sleep::for(200)->milliseconds();
            $game = GameState::find($gameId);
        }

        $this->info('Done.');
    }
}
