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
        return GameState::mutate($id, function (GameState $gameState) {
            $personality = Arr::random(PersonalityType::cases());

            $bot = new Bot(null, $personality);
            try {
                $gameState->addBot($bot);
            } catch (\Exception $e) {
                return response()->json($e->getMessage(), 500);
            }

            return response()->json($gameState->toArray());
        });
    }
}
