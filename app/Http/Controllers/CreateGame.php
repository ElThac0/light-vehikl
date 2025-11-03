<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Vehikl\LvObjects\GameObjects\Player;

class CreateGame extends Controller
{
    public const ARENA_SIZE = 50;

    public function __invoke(Request $request)
    {
        $gameState = new GameState(self::ARENA_SIZE);
        $player = new Player($request->session()->getId());

        $gameState->addPlayer($player);
        $request->session()->put('active_game', $gameState->getId());

        $gameState->save();

        $gameList = cache()->remember("game_list", 3600, function () {
            return [];
        });
        $gameList[] = $gameState->getId();
        cache()->put('game_list', $gameList);

        return response()->json($gameState->toArray());
    }
}
