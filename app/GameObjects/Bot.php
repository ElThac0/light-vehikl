<?php

namespace App\GameObjects;

use App\Enums\Direction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Bot
{
    private Arena $arena;
    private Player $player;
    private array $players;

    public function __construct(?Player $player = null)
    {
        $this->player = $player ?: new Player(Str::uuid()->toString());
    }
    public function readGame(array $game): void
    {
        $this->players = $game['players'];
        $this->arena = new Arena($game['arenaSize'], $game['tiles']);
        $this->playerId = Str::uuid();
    }

    public function decideMove(): Direction|null
    {
        $avoid = match ($this->player->direction) {
            Direction::NORTH => Direction::SOUTH,
            Direction::EAST => Direction::WEST,
            Direction::SOUTH => Direction::NORTH,
            Direction::WEST => Direction::EAST,
            default => Direction::NORTH,
        };

        $directions = collect(Direction::cases())
            ->filter(fn(Direction $direction) => $direction->value !== $avoid->value)
            ->filter(fn(Direction $direction) => ! in_array($direction->value, $this->player->avoidDirections()))
            ->values();

        return Arr::random($directions->toArray());
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

    public static function fromPlayer(Player $player): self
    {
        return new static($player);
    }

    public static function deserialize(): self
    {

    }
}
