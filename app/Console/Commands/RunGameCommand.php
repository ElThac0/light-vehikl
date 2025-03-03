<?php

namespace App\Console\Commands;

use App\GameObjects\GameState;
use Illuminate\Console\Command;

class RunGameCommand extends Command
{
    protected $signature = 'run:game {gameId}';

    protected $description = 'Command description';

    protected $sleepTime = 5000;

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        $game = GameState::findCache($gameId);
        $this->info('Running game...');
        while (!$game->isOver()) {
            $game->nextTick();
            \Illuminate\Support\Sleep::for(200)->milliseconds();
        }

        $this->info('Done.');
    }
}
