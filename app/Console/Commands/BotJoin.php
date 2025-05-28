<?php

namespace App\Console\Commands;

use App\Enums\GameStatus;
use App\GameObjects\GameState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BotJoin extends Command
{
    protected $signature = 'bot:join {gameId}';

    protected $description = 'Command description';

    protected string $host = 'http://localhost:8000';

    protected array $cookies;
    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        $this->joinGame($gameId);
    }

    protected function joinGame(string $gameId): string
    {
        $response = Http::post($this->host . '/join-game/' . $gameId)->json();

        $this->cookies = $response->getCookies();

        return 'ok';
    }
}
