<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoined;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GameController extends Controller
{
    public function __invoke(Request $request, string $id) {
        $startLocations = [['x' => 5, 'y' => 5], ['x' => 45, 'y' => 45]];
        $players = Cache::get('players') ?? [];
        $newPlayerNum = count($players);
        $players[] = new Player(['location' => $startLocations[$newPlayerNum], 'playerId' => $id]);
        Cache::remember('players', 60, function () use ($players) {
            return $players;
        });
        PlayerJoined::dispatch($players);
    }
}
