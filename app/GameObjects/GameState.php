<?php

namespace App\GameObjects;

use App\Enums\ContentType;
use App\Enums\Direction;
use App\Enums\PlayerStatus;
use App\Events\GameUpdated;
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
            new StartLocation(ContentType::PLAYER1, $this->getTile(0, 0), Direction::EAST),
            new StartLocation(ContentType::PLAYER2, $this->getTile(0, $this->maxY), Direction::NORTH),
            new StartLocation(ContentType::PLAYER3, $this->getTile($this->maxX, 0), Direction::SOUTH),
            new StartLocation(ContentType::PLAYER4, $this->getTile($this->maxY, $this->maxY), Direction::WEST),
        ];
    }

    public function getNextStartLocation(): StartLocation
    {
        $startIndex = count($this->getPlayers());
        return $this->getStartLocations()[$startIndex];
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getPlayer(ContentType $playerType): Player
    {
        return $this->players[$playerType->value];
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

        if ($this->isInGame($player)) {
            throw new Exception('Player already in game');
        }

        $start = $this->getNextStartLocation();
        $coords = $start->tile->getCoords();
        $playerEnum = $start->playerType;
        $player->setSlot($playerEnum);
        $this->players[$playerEnum->value] = $player->setLocation($coords)->setDirection($start->direction);
        $start->tile->setContents($playerEnum);
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

    public function nextTick(): void
    {
        foreach($this->getPlayers() as $playerSlot => $player) {
            $this->movePlayer($player);
        }
        GameUpdated::dispatch($this);
    }

    protected function movePlayer(Player $player): void
    {
        $playerType = $this->getPlayerType($player);
        $playerTrail = $playerType->trailType();
        $previousLocation = $player->getLocation();

        match ($player->direction) {
            Direction::NORTH => $player->moveNorth(),
            Direction::EAST => $player->moveEast(),
            Direction::SOUTH => $player->moveSouth(),
            Direction::WEST => $player->moveWest(),
        };

        $newLocation = $player->getLocation();

        if (!$this->validMove($newLocation)) {
            $player->setLocation($previousLocation);
            $player->setStatus(PlayerStatus::CRASHED);
        } else {
            $this->getTile(...$previousLocation)->setContents($playerTrail);
            $this->getTile(...$newLocation)->setContents($playerType);
        }
    }

    protected function getPlayerType(Player $player): ContentType
    {
        return $player->getSlot();
    }

    protected function validMove(array $location): bool
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

    protected function serializeArena(): array
    {
        return array_map([$this, 'serializeTile'], $this->arena);
    }

    protected function serializeTile(Tile $tile): ContentType
    {
        return $tile->getContents();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'arenaSize' => $this->arenaSize,
            'tiles' => $this->serializeArena(),
            'players' => array_values($this->players),
        ];
    }

    public function isInGame(Player $player): bool
    {
        foreach ($this->getPlayers() as $currentPlayer) {
            if ($player->getId() === $currentPlayer->getId()) {
                return true;
            }
        }
        return false;
    }
}
