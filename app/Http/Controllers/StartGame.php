<?php

namespace App\Http\Controllers;

use App\GameObjects\GameState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use LightVehikl\LvObjects\Enums\PlayerStatus;
use LightVehikl\LvObjects\GameObjects\Player;

class StartGame extends Controller
{
    public function __invoke(Request $request, $id)
    {
        return GameState::mutate($id, function (GameState $gameState) {
            if ($gameState->getPlayers()->count() < 2) {
                return response()->json('Not enough players', 500);
            }

            if ($gameState->getPlayers()->some(fn (Player $player) => $player->status !== PlayerStatus::READY)) {
                return response()->json('Not everyone is ready', 500);
            }

            Process::path(base_path())->start('php artisan run:game '.$gameState->getId());

            return response()->json($gameState->toArray());
        });
    }
}
