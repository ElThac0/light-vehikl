<?php

namespace App\GameObjects\Personalities;

use App\Enums\Direction;
use App\GameObjects\Arena;
use App\GameObjects\Player;

interface Personality
{
    public function decideMove(Arena $arena): Direction|null;
}
