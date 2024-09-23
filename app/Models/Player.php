<?php

namespace App\Models;


class Player
{

    public function __construct(public ?int $x = null, public ?int $y = null)
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

}
