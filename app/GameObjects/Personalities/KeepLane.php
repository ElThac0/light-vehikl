<?php

namespace App\GameObjects\Personalities;

use App\Enums\Direction;
use App\GameObjects\Arena;
use App\GameObjects\Player;
use Arr;

class KeepLane implements Personality
{

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

    protected function pickGoodMove(): Direction|null
    {
        $goodMoves = [];

        foreach (Direction::cases() as $direction) {
            if ($this->goodDirection($direction)) {
                $goodMoves[] = $direction;
            }
        }

        if (empty($goodMoves)) {
            return null;
        }

        return Arr::random($goodMoves);
    }

    protected function goodDirection(Direction $direction): bool
    {
        [$x, $y] = $this->player->getLocation();
        switch ($direction) {
            case Direction::NORTH:
                $y--;
                break;
            case Direction::SOUTH:
                $y++;
                break;
            case Direction::EAST:
                $x++;
                break;
            case Direction::WEST:
                $x--;
                break;
        }
        return $this->arena->validMove([$x, $y]);
    }
}
