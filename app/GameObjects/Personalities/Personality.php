<?php

namespace App\GameObjects\Personalities;

use LightVehikl\LvObjects\Enums\Direction;
use LightVehikl\LvObjects\GameObjects\Arena;

interface Personality
{
    public function decideMove(Arena $arena): Direction|null;
}
