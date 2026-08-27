<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use LightVehikl\LvObjects\GameObjects\Player;
use Symfony\Component\HttpFoundation\Response;

class JoinGame extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        return GameState::mutate($id, function (GameState $gameState) use ($request) {
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

            $request->session()->put('active_game', $gameState->getId());

            return response()->json([
                'gameState' => $gameState->toArray(),
                'yourId' => $player->id,
                'webSocketKey' => config('reverb.apps.apps.0.key'),
            ]);
        });
    }
}
