<?php

namespace App\Console\Commands;

use App\Events\PlayerJoined;
use Illuminate\Console\Command;

class FakeJoined extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:player-joined';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PlayerJoined::dispatch();
    }
}
