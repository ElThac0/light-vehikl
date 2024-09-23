<?php

namespace App;

class Tile
{
    private mixed $contents = null;

    public function __construct(public int $x, public int $y)
    {
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function getCoords(): array
    {
        return [$this->x, $this->y];
    }
    
    public function setContents(mixed $thing): self
    {
        $this->contents = $thing;
        return $this;
    }
    
    public function getContents(): mixed
    {
        return $this->contents;
    }

    public function isOccupied(): bool
    {
        return !!$this->contents;
    }
}
