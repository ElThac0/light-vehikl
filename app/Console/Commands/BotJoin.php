<?php

namespace App\Console\Commands;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use WebSocket\Client as WSClient;

class BotJoin extends Command
{
    protected $signature = 'bot:join {gameId}';

    protected $description = 'Command description';

    protected string $host = 'http://localhost:8000';
    protected string $gameId;
    protected array $gameState;
    protected string $playerId;
    protected CookieJar $cookies;

    protected $webClient;

    protected WSClient $ws;

    public function handle(): void
    {
        $this->gameId = $this->argument('gameId');

        $this->webClient = Http::buildClient();

        $this->joinGame($this->gameId);
        $this->connectWebsocket();
        $this->setReady();

        $this->listenForUpdates();
    }

    protected function joinGame(string $gameId): void
    {
        $response = $this->client()->post($this->host . '/join-game/' . $gameId);

        if (!$response->successful()) {
            $this->error('Failed to join: ' . $response->body());
            return;
        }

        $this->playerId = $response->json('yourId');
        $this->gameState = $response->json('gameState');
        $this->line('Joined as player: <info>' . $this->playerId . '</info>');
    }

    protected function setReady(): void
    {
        $this->client()->post($this->host . '/mark-ready/' . $this->gameId);
    }

    protected function client(): PendingRequest
    {
        return Http::setClient($this->webClient);
    }

    protected function connectWebsocket(): void
    {
        $this->ws = new WSClient('ws://localhost:8080/app/wjfen0ggyfrmkspsekrt');
        $this->ws
            // Add standard middlewares
            ->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new \WebSocket\Middleware\FollowRedirect());

        // Must send subscribe to channel message here.
    }

    protected function listenForUpdates(): void
    {
        $this->ws->onText(function (WSClient $client, \WebSocket\Connection $connection, \WebSocket\Message\Message $message) {
            // Act on incoming message
            echo "Got message: {$message->getContent()} \n";
        })->start();
    }
}
