<?php

namespace App\Http\Controllers;

use App\Enums\Direction;
use App\GameObjects\GameState;
use App\GameObjects\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameMove extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $gameState = GameState::find($id);

        $playerId = $request->session()->getId();

        $player = $gameState->findPlayer($playerId);

        $direction = Direction::tryFrom($request->input('direction'));

        if (!$direction) {
            return response()->json('Bad direction', 422);
        }

        $player->setDirection($direction);

        $gameState->save();

        return response()->json($gameState->toArray());
    }
}
