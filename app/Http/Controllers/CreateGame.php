<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use App\GameObjects\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class CreateGame extends Controller
{
    public const ARENA_SIZE = 25;

    public function __invoke(Request $request)
    {
        $gameState = new GameState(self::ARENA_SIZE);
        $player = new Player($request->session()->getId());

        $gameState->addPlayer($player);
        $request->session()->put('active_game', $gameState->getId());

        $gameState->save();

        $gameList = Cache::remember("game_list", 3600, function () {
            return [];
        });
        $gameList[] = $gameState->getId();

        Process::path(base_path())->start('php artisan run:game ' . $gameState->getId());

        return response()->json($gameState->toArray());
    }
}
