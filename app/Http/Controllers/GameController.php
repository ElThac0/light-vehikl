<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoined;
use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;
use JetBrains\PhpStorm\NoReturn;

class GameController extends Controller
{
    public function __invoke(Request $request, string $id) {
        PlayerJoined::dispatch(new Player(['x' => 5, 'y' => 5, 'playerId' => $id]));
    }
}
