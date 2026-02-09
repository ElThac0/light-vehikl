<?php

namespace App\GameObjects\Personalities;

use LightVehikl\LvObjects\Enums\Direction;
use LightVehikl\LvObjects\GameObjects\Arena;
use App\GameObjects\Personalities\Traits\PicksGoodMoves;
use LightVehikl\LvObjects\GameObjects\Player;

class ChangeDirection implements Personality
{
    use PicksGoodMoves;

    private Arena $arena;
    private Player $player;

    public function __construct()
    {
    }

    public function updatePlayer(Player $player) {
        $this->player = $player;
    }

    public function decideMove(Arena $arena): ?Direction
    {
        $this->arena = $arena;

        return $this->pickGoodMove();
    }
}
