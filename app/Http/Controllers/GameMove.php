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

        return GameState::mutate($id, function (GameState $gameState) use ($request, $direction) {
            $player = $gameState->findPlayer($request->session()->getId());

            if (! $player) {
                return response()->json('Player not in game', 404);
            }

            $player->setDirection($direction);

            return response()->json($gameState->toArray());
        });
    }
}
