<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use App\Support\SessionPlayer;
use Illuminate\Http\Request;

class MarkReady extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $game = GameState::mutate($id, fn (GameState $gameState) => $gameState->setReady(SessionPlayer::id($request->session())));

        return response()->json($game->toArray());
    }
}
