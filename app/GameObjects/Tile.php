<?php

namespace App\GameObjects;

use App\Enums\ContentType;

class Tile
{
    private ContentType $contents = ContentType::EMPTY;

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
    
    public function setContents(ContentType $thing): self
    {
        $this->contents = $thing;
        return $this;
    }
    
    public function getContents(): ContentType
    {
        return $this->contents;
    }

    public function isOccupied(): bool
    {
        return $this->contents !== ContentType::EMPTY;
    }
}
