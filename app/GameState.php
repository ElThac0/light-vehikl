<?php

namespace App;

use App\Models\Player;

class GameState
{
    public array $arena;
    public array $players = [];

    public function __construct(protected int $arenaSize)
    {
        for ($x = 0; $x < $this->arenaSize; $x++) {
            for ($y = 0; $y < $this->arenaSize; $y++) {
                $this->arena[] = new Tile($x, $y);
            }
        }
    }

    public function getTile(int $x, int $y): Tile
    {
        $addr = $x * $this->arenaSize + $y;
        return $this->arena[$addr];
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function addPlayer(Player $player): void
    {
        $this->players[] = $player->setLocation([1, 1]);
    }
    public function toArray(): array
    {
        return $this->arena;
    }
}
