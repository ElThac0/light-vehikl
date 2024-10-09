<?php

namespace App;

use App\Models\Player;
use Exception;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class GameState
{
    const MAX_PLAYERS = 4;
    public array $arena;
    public array $players = [];
    protected int $maxX, $maxY;
    protected string $id;

    public function __construct(protected int $arenaSize)
    {
        for ($x = 0; $x < $this->arenaSize; $x++) {
            for ($y = 0; $y < $this->arenaSize; $y++) {
                $this->arena[] = new Tile($x, $y);
            }
        }
        $this->maxX = $this->maxY = $this->arenaSize - 1;
        $this->id = Uuid::uuid4();
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

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @throws Exception
     */
    public function addPlayer(Player $player): void
    {
        if (count($this->players) >= self::MAX_PLAYERS) {
            throw new Exception('Max players reached');
        }

        $location = $this->getNextStartLocation();
        $this->players[] = $player->setLocation($location->getCoords());
        $location->setContents($player);
    }

    public function save(): self
    {
        Cache::set("game_state.{$this->id}", $this);
        return $this;
    }

    public static function find($id): ?GameState
    {
        return Cache::get("game_state.{$id}");
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'arenaSize' => $this->arenaSize,
            'tiles' => $this->arena,
            'players' => $this->players,
        ];
    }
}
