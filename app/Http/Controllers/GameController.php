<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoined;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index() {
        PlayerJoined::dispatch('taco');
        return Inertia::render('Game/Index', []);
    }
}
