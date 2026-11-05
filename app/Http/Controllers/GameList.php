<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;

class GameList extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json(GameState::summaries());
    }
}
