<?php

namespace App\Console\Commands;

use LightVehikl\LvObjects\GameObjects\Arena;
use App\GameObjects\Personalities\KeepLane;
use LightVehikl\LvObjects\GameObjects\Player;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use WebSocket\Client as WSClient;

class BotJoin extends Command
{
    protected $signature = 'bot:join {gameId}';

    protected $description = 'Add a bot to a game.';

    protected string $host = 'http://localhost:8000';
    protected string $webSocketHost = 'ws://localhost:8080/app/';
    protected string $gameId;
    protected array $gameState;
    protected string $playerId;
    protected CookieJar $cookies;

    protected $webClient;

    protected WSClient $ws;

    public function handle(): void
    {
        $this->gameId = $this->argument('gameId');

        $this->webClient = Http::setClient(Http::buildClient());

        $this->joinGame($this->gameId);
        $this->connectWebsocket();
        $this->setReady();

        $this->listenForUpdates();
    }

    protected function joinGame(string $gameId): void
    {
        $response = $this->webClient->post($this->host . '/join-game/' . $gameId);

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
        $this->webClient->post($this->host . '/mark-ready/' . $this->gameId);
    }

    protected function connectWebsocket(): void
    {
        $key = config('reverb.apps.apps.0.key');
        $this->line('Connecting to websocket with key ' . $key);
        $this->ws = new WSClient($this->webSocketHost . $key . '?protocol=7');
        $this->ws
            // Add standard middlewares
            ->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            ->addMiddleware(new \WebSocket\Middleware\FollowRedirect());

        $channelName = "GameChannel-{$this->gameId}";

        $subscribePayload = [
            'event' => 'pusher:subscribe',
            'data' => [
                'auth' => 'asdf',
                'channel' => $channelName,
            ]
        ];

        $this->line("Subscribing to <info>{$channelName}</info>");
        $this->ws->text(json_encode($subscribePayload));
    }

    protected function listenForUpdates(): void
    {
        $this->ws->onText(function (WSClient $client, \WebSocket\Connection $connection, \WebSocket\Message\Message $message) {
            $content = json_decode($message->getContent());
            $this->parseUpdate($content);
        })->start();
    }

    private function parseUpdate($content): void {
        switch ($content->event) {
            case 'game.updated':
                $this->handleGameUpdate(json_decode($content->data, true));
                break;
            default:
                $this->error('unknown event: ' . $content->event);
        }
    }

    private function handleGameUpdate($data): void {
        $arena = new Arena($data['arenaSize'], $data['tiles']);
        $tick = $data['tick'];

        $playerData = collect($data['players'])->first(fn(array $player) => $player['id'] === $this->playerId);
        $player = Player::deserialize($playerData);
        $personality = new KeepLane($player);
        $move = $personality->decideMove($arena);
        if ($move) {
            $this->webClient->post($this->host . "/game/{$this->gameId}/move", ['direction' => $move->value]);
            $this->info("[{$tick}:{$data['status']}] Changed direction to <info>{$move->value}</info>");
        } else {
            $this->info("[{$tick}:{$data['status']}] No move.");
        }
    }
}
