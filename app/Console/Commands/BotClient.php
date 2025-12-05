<?php

namespace App\Console\Commands;

use App\GameObjects\Personalities\KeepLane;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use LightVehikl\LvObjects\GameObjects\Arena;
use LightVehikl\LvObjects\GameObjects\Player;
use WebSocket\Client as WSClient;

class BotClient
{
    private PendingRequest $webClient;
    public function __construct(public string $host) {

    }

    /**
     * @throws Exception
     */
    protected function joinGame(string $gameId): void
    {
        $this->webClient = Http::setClient(Http::buildClient());

        $response = $this->webClient->post($this->host . '/join-game/' . $gameId);

        if (!$response->successful()) {
            throw new Exception('Failed to join: ' . $response->body());
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
