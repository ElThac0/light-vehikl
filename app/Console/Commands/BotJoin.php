<?php

namespace App\Console\Commands;

use App\GameObjects\Personalities\KeepLane;
use Illuminate\Console\Command;
use WebSocket\Client as WSClient;

class BotJoin extends Command
{
    protected $signature = 'bot:join {gameId}';

    protected $description = 'Add a bot to a game.';

    protected string $host = 'http://localhost:8000';
    protected string $webSocketHost = 'ws://localhost:8080/app/';
    protected string $gameId;
    protected BotClient $client;

    protected $webClient;

    protected WSClient $ws;

    public function handle(): void
    {
        $this->gameId = $this->argument('gameId');

        $this->client = new BotClient(new KeepLane(), $this->host, $this->webSocketHost, fn ($text) => $this->line($text));

        $this->client->connect($this->gameId);
    }
}
