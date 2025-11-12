<?php

namespace App\GameObjects\Personalities;

use LightVehikl\LvObjects\Enums\Direction;
use LightVehikl\LvObjects\GameObjects\Arena;
use App\GameObjects\Personalities\Traits\PicksGoodMoves;
use LightVehikl\LvObjects\GameObjects\Player;

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
