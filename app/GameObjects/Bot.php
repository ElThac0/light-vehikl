<?php

namespace App\GameObjects;

use App\Enums\Direction;
use App\Enums\PersonalityType;
use App\GameObjects\Personalities\KeepLane;
use App\GameObjects\Personalities\Personality;
use Illuminate\Support\Str;

class Bot
{
    public Arena $arena;
    private Player $player;

    private Personality $personality;

    public function __construct(?Player $player = null, PersonalityType $personality = null)
    {
        $this->player = $player ?: new Player(Str::uuid()->toString());
        $this->personality = $personality ? new $personality->value($this->player) : new KeepLane($this->player);
    }

    public function decideMove(): Direction|null
    {
        return $this->personality->decideMove($this->arena);
    }

    public function updatePlayer(): void
    {
        $move = $this->decideMove();
        if ($move) {
            $this->player->setDirection($move);
        }
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
