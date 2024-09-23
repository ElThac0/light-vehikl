<?php

namespace App;

use App\Models\Player;

class GameState
{
    const MAX_PLAYERS = 4;
    public array $arena;
    public array $players = [];
    protected int $maxX, $maxY;

    public function __construct(protected int $arenaSize)
    {
        for ($x = 0; $x < $this->arenaSize; $x++) {
            for ($y = 0; $y < $this->arenaSize; $y++) {
                $this->arena[] = new Tile($x, $y);
            }
        }
        $this->maxX = $this->maxY = $this->arenaSize - 1;
    }

    public function getTile(int $x, int $y): Tile
    {
        $addr = $x * $this->arenaSize + $y;
        return $this->arena[$addr];
    }

    public function getMaxPlayers(): int
    {
        return self::MAX_PLAYERS;
    }

    public function getStartLocations(): array
    {
        return [
            $this->getTile(0, 0),
            $this->getTile(0, $this->maxY),
            $this->getTile($this->maxX, 0),
            $this->getTile($this->maxY, $this->maxY),
        ];
    }

    public function getNextStartLocation(): Tile
    {
        return $this->getStartLocations()[count($this->getPlayers())];
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function addPlayer(Player $player): void
    {
        $location = $this->getNextStartLocation();
        $this->players[] = $player->setLocation($location->getCoords());
        $location->setContents($player);
    }

    public function toArray(): array
    {
        return $this->arena;
    }
}
