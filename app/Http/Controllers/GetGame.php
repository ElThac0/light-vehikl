<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GetGame extends Controller
{
    public function __invoke(Request $request)
    {
        $gameState = Cache::get("game_state.{$request->session()->get('active_game')}");
        return response()->json($gameState?->toArray() ?? null);
    }
}
