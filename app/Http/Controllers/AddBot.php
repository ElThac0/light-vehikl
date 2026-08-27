<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use LightVehikl\LvObjects\Enums\PersonalityType;
use LightVehikl\LvObjects\GameObjects\Bot;

class AddBot extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        $game = GameState::mutate($id, fn (GameState $gameState) => $gameState->addBot(new Bot(null, Arr::random(PersonalityType::cases()))));

        return response()->json($game->toArray());
    }
}
