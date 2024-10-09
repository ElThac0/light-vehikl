<?php

namespace App\Models;


use App\PlayerStatus;

class Player
{
    protected PlayerStatus $status = PlayerStatus::WAITING;

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
    }

    public function getStatus(): PlayerStatus
    {
        return $this->status;
    }

}
