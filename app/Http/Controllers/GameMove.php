<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use LightVehikl\LvObjects\Enums\Direction;

class GameMove extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $direction = Direction::tryFrom($request->input('direction'));

        if (! $direction) {
            return response()->json('Bad direction', 422);
        }

        $game = GameState::mutate($id, fn (GameState $gameState) => $gameState->setPlayerDirection($request->session()->getId(), $direction));

        return response()->json($game->toArray());
    }
}
