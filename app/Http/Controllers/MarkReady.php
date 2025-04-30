<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Exception;
use Illuminate\Http\Request;

class MarkReady extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        // TODO: if game ID is not valid, this should fail
        $gameState = GameState::find($id);

        try {
            $gameState->setReady($request->session()->getId());
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        $gameState->save();

        return response()->json($gameState->toArray());
    }
}
