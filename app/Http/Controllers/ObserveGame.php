<?php

namespace App\Http\Controllers;

use App\Exceptions\GameNotFound;
use App\GameObjects\GameState;

/**
 * Lets a browser watch a game without taking part in it. Observers aren't added
 * to the game; they just get the current state and what they need to subscribe
 * to the public GameChannel-{id} updates.
 */
class ObserveGame extends Controller
{
    public function __invoke(string $id)
    {
        $game = GameState::find($id) ?? throw GameNotFound::for($id);

        return response()->json([
            'gameState' => $game->toArray(),
            'channel' => 'GameChannel-'.$game->getId(),
            'webSocketKey' => config('reverb.apps.apps.0.key'),
        ]);
    }
}
