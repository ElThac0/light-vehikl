<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LightVehikl\LvObjects\GameObjects\Player;

class JoinGame extends Controller
{
    public function __invoke(Request $request, string $id) {
        $gameState = GameState::find($id);

        if (!$gameState) {
            return response()->json('Game not found', Response::HTTP_NOT_FOUND);
        }

        $playerId = $request->session()->getId();

        if ($gameState->findPlayer($playerId)) {
            return response()->json($gameState->toArray());
        }

        if ($gameState->isOver()) {
            return response()->json('Game is over', Response::HTTP_GONE);
        }

        if ($gameState->isActive()) {
            return response()->json('Game already started', Response::HTTP_FORBIDDEN);
        }

        $player = new Player($playerId);

        try {
            $gameState->addPlayer($player);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        $gameState->save();

        $request->session()->put('active_game', $gameState->getId());

        return response()->json([
            'gameState' => $gameState->toArray(),
            'yourId' => $player->id,
            'webSocketKey' => config('reverb.apps.apps.0.key'),
        ]);
    }
}
