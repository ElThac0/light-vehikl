<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class StartGame extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $game = GameState::mutate($id, function (GameState $gameState) {
            $gameState->ensureStartable();

            Process::path(base_path())->start('php artisan run:game '.$gameState->getId());
        });

        return response()->json($game->toArray());
    }
}
