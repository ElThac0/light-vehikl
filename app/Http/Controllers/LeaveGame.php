<?php

namespace App\Http\Controllers;

use App\Exceptions\GameNotFound;
use App\Exceptions\PlayerNotInGame;
use App\GameObjects\GameState;
use Illuminate\Http\Request;

class LeaveGame extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $request->session()->remove('active_game');

        try {
            $game = GameState::mutate($id, fn (GameState $gameState) => $gameState->leave($request->session()->getId()));
        } catch (GameNotFound|PlayerNotInGame) {
            return response()->json('ok');
        }

        GameState::removeIfAbandoned($game);

        return response()->json('ok');
    }
}
