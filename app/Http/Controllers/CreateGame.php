<?php

namespace App\Http\Controllers;

use App\Events\GameCreated;
use App\GameObjects\GameState;
use Illuminate\Http\Request;
use LightVehikl\LvObjects\GameObjects\Player;

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
        $gameState->track();

        GameCreated::dispatch($gameState);

        return response()->json($gameState->toArray());
    }
}
