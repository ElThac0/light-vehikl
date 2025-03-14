<?php

namespace App\Http\Controllers;

use App\GameObjects\Bot;
use App\GameObjects\GameState;
use Illuminate\Http\Request;

class AddBot extends Controller
{
    public function __invoke(Request $request, string $id) {
        $gameState = GameState::find($id);

        $bot = new Bot();
        try {
            $gameState->addBot($bot);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        $gameState->save();

        return response()->json($gameState->toArray());
    }
}
