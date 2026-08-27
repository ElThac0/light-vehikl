<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;

class JoinGame extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $playerId = $request->session()->getId();

        $game = GameState::mutate($id, fn (GameState $gameState) => $gameState->join($playerId));

        $request->session()->put('active_game', $game->getId());

        return response()->json([
            'gameState' => $game->toArray(),
            'yourId' => $playerId,
            'webSocketKey' => config('reverb.apps.apps.0.key'),
        ]);
    }
}
