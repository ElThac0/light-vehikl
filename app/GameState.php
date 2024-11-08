<?php

namespace App;

use App\Enums\ContentType;
use App\Events\GameUpdated;
use App\Models\Player;
use Exception;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;
use App\Enums\Direction;

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
            ContentType::PLAYER1->value => [$this->getTile(0, 0), Direction::EAST],
            ContentType::PLAYER2->value => [$this->getTile(0, $this->maxY), Direction::NORTH],
            ContentType::PLAYER3->value => [$this->getTile($this->maxX, 0), Direction::SOUTH],
            ContentType::PLAYER4->value => [$this->getTile($this->maxY, $this->maxY), Direction::WEST],
        ];
    }

    public function getNextStartLocation(): array
    {
        $nextPlayerCount = count($this->getPlayers()) + 1;
        $nextPlayerType = ContentType::playerByNumber($nextPlayerCount);
        return [$nextPlayerType, $this->getStartLocations()[$nextPlayerType->value]];
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

        if ($this->isInGame($player)) {
            throw new Exception('Player already in game');
        }

        [$playerEnum, $vector] = $this->getNextStartLocation();
        [$location, $direction] = $vector;
        $coords = $location->getCoords();
        $this->players[] = $player->setLocation($coords)->setDirection($direction);
        $location->setContents($playerEnum);
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
        foreach($this->getPlayers() as $player) {
            $this->movePlayer($player);
        }
        GameUpdated::dispatch($this);
    }

    protected function movePlayer(Player $player): void
    {
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
            $this->getTile(...$previousLocation)->setContents('trail');
            $this->getTile(...$newLocation)->setContents($player);
        }
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

    protected function serializeArena(): string
    {
        return array_reduce($this->arena, function (string $carry, Tile $tile) { return $carry . $tile->getContents(); }, "");
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
            'tiles' => $this->arena,
            'players' => $this->players,
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
