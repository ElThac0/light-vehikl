<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use App\GameObjects\Player;
use Illuminate\Http\Request;

class JoinGame extends Controller
{
    public function __invoke(Request $request, string $id) {
        $gameState = GameState::find($id);

        if (!$gameState) {
            return response()->json('Game not found', 404);
        }

        $playerId = $request->session()->getId();

        if ($gameState->findPlayer($playerId)) {
            return response()->json($gameState->toArray());
        }

        $player = new Player($playerId);

        try {
            $gameState->addPlayer($player);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        $gameState->save();

        $request->session()->put('active_game', $gameState->getId());

        return response()->json($gameState->toArray());
    }
}
