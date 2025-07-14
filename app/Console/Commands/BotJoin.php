<?php

namespace App\Console\Commands;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class BotJoin extends Command
{
    protected $signature = 'bot:join {gameId}';

    protected $description = 'Command description';

    protected string $host = 'http://localhost:8000';
    protected string $gameId;
    protected string $playerId;
    protected CookieJar $cookies;

    protected $client;

    public function handle(): void
    {
        $this->gameId = $this->argument('gameId');

        $this->client = Http::buildClient();

        $this->joinGame($this->gameId);
        $this->setReady();
    }

    protected function joinGame(string $gameId): void
    {
        $response = $this->client()->post($this->host . '/join-game/' . $gameId);

        if (!$response->successful()) {
            $this->error('Failed to join: ' . $response->body());
            return;
        }

        $this->playerId = $response->json('yourId');
        $this->line('Joined as player: <info>' . $this->playerId . '</info>');
    }

    protected function setReady(): void
    {
        $this->client()->post($this->host . '/mark-ready/' . $this->gameId);
    }

    protected function client(): PendingRequest
    {
        return Http::setClient($this->client);
    }
}
