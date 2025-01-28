<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;

class LeaveGame extends Controller
{
    public function __invoke(Request $request, string $id) {
        $gameState = GameState::find($id);

        $playerId = $request->session()->getId();

        // is player in game?

        $request->session()->remove('active_game');

        return response()->json('ok');
    }
}
