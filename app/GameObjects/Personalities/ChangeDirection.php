<?php

namespace App\GameObjects\Personalities;

use Vehikl\LvObjects\Enums\Direction;
use Vehikl\LvObjects\GameObjects\Arena;
use App\GameObjects\Personalities\Traits\PicksGoodMoves;
use Vehikl\LvObjects\GameObjects\Player;

class ChangeDirection implements Personality
{
    use PicksGoodMoves;

    private Arena $arena;

    public function __construct(private Player $player)
    {
    }

    public function decideMove(Arena $arena): ?Direction
    {
        $this->arena = $arena;

        return $this->pickGoodMove();
    }
}
