<?php

namespace App\GameObjects\Personalities;

use App\Enums\Direction;
use App\GameObjects\Arena;
use App\GameObjects\Personalities\Traits\PicksGoodMoves;
use App\GameObjects\Player;

class KeepLane implements Personality
{
    use PicksGoodMoves;

    private Arena $arena;

    public function __construct(private Player $player)
    {
    }

    public function decideMove(Arena $arena): ?Direction
    {
        $this->arena = $arena;

        if ($this->goodDirection($this->player->direction)) {
            return null;
        }

        return $this->pickGoodMove();
    }
}
