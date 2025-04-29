<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class StartGame extends Controller
{
    public function __invoke(Request $request, $gameId)
    {
        $gameState = GameState::find($gameId);

        Process::path(base_path())->start('php artisan run:game ' . $gameState->getId());

        return response()->json($gameState->toArray());
    }
}
