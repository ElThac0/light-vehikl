<?php

namespace App\GameObjects\Personalities\Traits;

use App\Enums\Direction;
use Illuminate\Support\Arr;

trait PicksGoodMoves
{
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
