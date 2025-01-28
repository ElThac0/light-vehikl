<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;

class GetGame extends Controller
{
    public function __invoke(Request $request)
    {
        $gameState = GameState::find($request->session()->get('active_game'));

        return response()->json($gameState?->toArray() ?? null);
    }
}
