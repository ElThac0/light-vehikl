<?php

namespace App\Http\Controllers;

use LightVehikl\LvObjects\PersonalityType;
use App\GameObjects\Bot;
use App\GameObjects\GameState;
use App\GameObjects\Personalities\ChangeDirection;
use App\GameObjects\Personalities\KeepLane;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AddBot extends Controller
{
    public function __invoke(Request $request, string $id) {
        $gameState = GameState::find($id);

        $knownPersonalities = PersonalityType::cases();

        $personality = Arr::random($knownPersonalities);

        $bot = new Bot(null, $personality);
        try {
            $gameState->addBot($bot);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        $gameState->save();

        return response()->json($gameState->toArray());
    }
}
