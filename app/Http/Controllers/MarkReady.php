<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Exception;
use Illuminate\Http\Request;

class MarkReady extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        return GameState::mutate($id, function (GameState $gameState) use ($request) {
            try {
                $gameState->setReady($request->session()->getId());
            } catch (Exception $e) {
                return response()->json($e->getMessage(), 500);
            }

            return response()->json($gameState->toArray());
        });
    }
}
