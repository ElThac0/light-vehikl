<?php

namespace App\Events;

use App\GameObjects\GameState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GameCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameState $game)
    {

    }

    public function broadcastOn(): array
    {
        return [
            new Channel('GameChannel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.created';
    }

    public function broadcastWith(): array
    {
        $gameList = Cache::remember('game_list', 3600, function () {
            return [];
        });
        return ['games' => $gameList];
    }
}
