<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use App\GameObjects\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AddPlayer extends Controller
{
    public const ARENA_SIZE = 25;

    public function __invoke(Request $request, string $id) {
        $gameState = Cache::remember("game_state.{$id}", 3600, function () {
            return new GameState(self::ARENA_SIZE);
        });

        $playerId = $request->session()->getId();

        $player = new Player($playerId);
        $gameState->addPlayer($player);
        $gameState->save();

        $request->session()->put('active_game', $gameState->getId());

        return response()->json($gameState->toArray());
    }
}
