<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;

class MarkReady extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $game = GameState::mutate($id, fn (GameState $gameState) => $gameState->setReady($request->session()->getId()));

        return response()->json($game->toArray());
    }
}
