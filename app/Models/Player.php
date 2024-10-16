<?php

namespace App\Models;


use App\Enums\Direction;
use App\PlayerStatus;

class Player
{
    protected PlayerStatus $status = PlayerStatus::WAITING;
    public int $x;
    public int $y;
    public Direction $direction;

    public function __construct(public string $id)
    {

    }

    public function getLocation(): array
    {
        return [$this->x, $this->y];
    }

    public function setLocation(array $coordinates): self
    {
        $this->x = $coordinates[0];
        $this->y = $coordinates[1];

        return $this;
    }

    public function setStatus(PlayerStatus $playerStatus): self
    {
        $this->status = $playerStatus;

        return $this;
    }

    public function getStatus(): PlayerStatus
    {
        return $this->status;
    }

    public function setDirection(Direction $direction): Player
    {
        $this->direction = $direction;

        return $this;
    }

    public function moveNorth(): void
    {
        $this->y--;
    }

    public function moveEast(): void
    {
        $this->x++;
    }

    public function moveSouth(): void
    {
        $this->y++;
    }

    public function moveWest(): void
    {
        $this->x--;
    }
}
