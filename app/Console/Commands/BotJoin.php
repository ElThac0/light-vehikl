<?php

namespace App\Console\Commands;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class BotJoin extends Command
{
    protected $signature = 'bot:join {gameId}';

    protected $description = 'Command description';

    protected string $host = 'http://localhost:8000';
    protected string $gameId;

    protected CookieJar $cookies;
    public function handle(): void
    {
        $this->gameId = $this->argument('gameId');

        $this->joinGame($this->gameId);
        $this->setReady();
    }

    protected function joinGame(string $gameId): void
    {
        $response = Http::post($this->host . '/join-game/' . $gameId);
//        dd($response);
        $this->cookies = $response->cookies;
    }

    protected function setReady(): void
    {
        $response = Http::post($this->host . '/mark-ready/' . $this->gameId);
        dump($response);
    }
}
