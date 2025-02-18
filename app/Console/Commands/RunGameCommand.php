<?php

namespace App\Console\Commands;

use App\GameObjects\GameState;
use Illuminate\Console\Command;
use Laravel\Octane\Octane;

class RunGameCommand extends Command
{
    protected $signature = 'run:game {gameId}';

    protected $description = 'Command description';

    protected $sleepTime = 5000;

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        $game = GameState::find($gameId);
        $this->info('Running game...');
        \Illuminate\Support\Sleep::for(200)->milliseconds();
        $this->info('Done.');
    }
}
