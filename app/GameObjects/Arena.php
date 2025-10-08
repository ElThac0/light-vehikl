<?php

namespace App\GameObjects;

use App\Enums\ContentType;
use App\Enums\Direction;

class Arena
{
    protected array $arena;
    protected int $maxX, $maxY;

    public function __construct(protected int $arenaSize, protected ?array $state = null)
    {
        $this->maxX = $this->maxY = $this->arenaSize - 1;
        if ($state === null) {
            for ($y = 0; $y < $this->arenaSize; $y++) {
                for ($x = 0; $x < $this->arenaSize; $x++) {
                    $this->arena[] = new Tile($x, $y);
                }
            }
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

    public function validMove(array $location): bool
    {
        return (
            $this->withinBounds($location) && !$this->getTile(...$location)->isOccupied()
        );
    }

    protected function withinBounds(array $location): bool
    {
        return (
            $location[0] >= 0 &&
            $location[1] >= 0 &&
            $location[0] < $this->arenaSize &&
            $location[1] < $this->arenaSize
        );
    }

    protected function deserialize(array $state): void
    {
        $tiles = $state;
        array_map(function ($tile, $index) {
            [$x, $y] = $this->keyToXY($index);
            $this->arena[] = new Tile($x, $y, ContentType::tryFrom($tile));
        }, $tiles, array_keys($tiles));
    }

    public function serialize(): array
    {
        return array_map([$this, 'serializeTile'], $this->arena);
    }

    protected function serializeTile(Tile $tile): int
    {
        return $tile->getContents()->value;
    }

    public function getStartLocations(): array
    {
        return [
            new StartLocation(ContentType::PLAYER1, $this->getTile(0, 0), Direction::EAST),
            new StartLocation(ContentType::PLAYER2, $this->getTile(0, $this->maxY), Direction::NORTH),
            new StartLocation(ContentType::PLAYER3, $this->getTile($this->maxX, 0), Direction::SOUTH),
            new StartLocation(ContentType::PLAYER4, $this->getTile($this->maxY, $this->maxY), Direction::WEST),
        ];
    }
}
