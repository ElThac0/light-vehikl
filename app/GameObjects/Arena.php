<?php

namespace App\GameObjects;

use App\Enums\ContentType;

class Arena
{
    protected array $arena;
    protected int $maxX, $maxY;

    public function __construct(protected int $arenaSize, protected ?array $state = null)
    {
        if ($state === null) {
            for ($y = 0; $y < $this->arenaSize; $y++) {
                for ($x = 0; $x < $this->arenaSize; $x++) {
                    $this->arena[] = new Tile($x, $y);
                }
            }
            $this->maxX = $this->maxY = $this->arenaSize - 1;
        } else {
            $this->deserialize($state);
        }
    }

    public function getTile(int $x, int $y): Tile
    {
        $addr = $y * $this->arenaSize + $x;
        return $this->arena[$addr];
    }

    public function keyToXY(int $key): array
    {
        $y = floor($key / $this->arenaSize);
        $x = $key - ($y * $this->arenaSize);
        return [$x, $y];
    }

    protected function deserialize(array $state): void
    {
        $tiles = $state;
        array_map(function ($tile, $index) {
            [$x, $y] = $this->keyToXY($index);
            $this->arena[] = new Tile($x, $y, ContentType::tryFrom($tile));
        }, $tiles, array_keys($tiles));
    }
}
