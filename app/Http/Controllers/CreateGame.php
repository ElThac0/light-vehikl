<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoined;
use App\GameState;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CreateGame extends Controller
{
    public const ARENA_SIZE = 25;

    public function __invoke(Request $request)
    {
        $gameState = new GameState(self::ARENA_SIZE);

        $player = new Player();

        $gameState->addPlayer($player);

        Cache::set("game_state.{$gameState->getId()}", $gameState);

        PlayerJoined::dispatch($gameState);
    }
}
