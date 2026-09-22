<?php

namespace App\Events;

use App\GameObjects\GameState;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameRemoved implements ShouldBroadcast
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
        return 'game.removed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->game->getId(),
            'games' => GameState::list(),
        ];
    }
}
