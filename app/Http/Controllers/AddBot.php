<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use LightVehikl\LvObjects\Enums\PersonalityType;
use LightVehikl\LvObjects\GameObjects\Bot;

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
