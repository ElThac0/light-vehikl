<?php

namespace App\GameObjects;

use App\Enums\Direction;
use Illuminate\Support\Str;

class Bot
{
    private Arena $arena;
    private Player $player;
    private array $players;

    public function __construct()
    {
        $this->player = new Player(Str::uuid()->toString());
    }
    public function readGame(array $game): void
    {
        $this->players = $game['players'];
        $this->arena = new Arena($game['arenaSize'], $game['tiles']);
        $this->playerId = Str::uuid();
    }

    public function decideMove(): Direction|null
    {
        // rando!
        return array_rand(Direction::cases());
    }

    public function updatePlayer(): void
    {
        $move = $this->decideMove();
        if ($move) {
            $this->player->setDirection($move);
        }
    }

    public function getPlayerId(): string
    {
        return $this->player->getId();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
