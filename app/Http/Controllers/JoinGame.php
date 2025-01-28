<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use App\GameObjects\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class JoinGame extends Controller
{
    public function __invoke(Request $request, string $id) {
        $gameState = GameState::find($id);

        $playerId = $request->session()->getId();

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
