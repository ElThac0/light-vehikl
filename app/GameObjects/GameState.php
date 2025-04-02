<?php

namespace App\GameObjects;

use App\Enums\ContentType;
use App\Enums\Direction;
use App\Enums\PlayerStatus;
use App\Events\GameUpdated;
use App\Traits\PersistInCache;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;
use Ramsey\Uuid\Uuid;

class GameState
{
    use PersistInCache;

    const MAX_PLAYERS = 4;

    protected string $id;
    public Arena $arena;
    protected array $players = [];
    /** @var array<Bot> $bots */
    protected array $bots = [];
    protected int $maxX, $maxY;

    public function __construct(protected int $arenaSize, $id = null)
    {
        $this->arena = new Arena($arenaSize);
        $this->maxX = $this->maxY = $this->arenaSize - 1;
        $this->id = $id ?? Uuid::uuid4()->toString();
    }

    public function getMaxPlayers(): int
    {
        return self::MAX_PLAYERS;
    }

    public function getNextStartLocation(): StartLocation
    {
        $startIndex = count($this->getPlayers());
        return $this->arena->getStartLocations()[$startIndex];
    }

    public function getPlayers(): Collection
    {
        return collect($this->players);
    }

    public function findPlayer(string $playerId): Player
    {
        return $this->getPlayers()->first(fn (Player $player) => $player->getId() === $playerId);
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
    public function addPlayer(Player $player): ContentType
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

        return $playerEnum;
    }

    /**
     * @param Bot $bot
     * @return void
     * @throws Exception
     */
    public function addBot(Bot $bot): void
    {
        $position = $this->addPlayer($bot->getPlayer());
        $this->bots[$position->value] = $bot;
    }

    public function nextTick(): void
    {
        foreach($this->bots as $bot) {
            $bot->updatePlayer();
        }

        foreach($this->getPlayers() as $playerSlot => $player) {
            if ($player->getStatus() !== PlayerStatus::CRASHED) {
                $this->movePlayer($player);
            }
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

        if (!$this->arena->validMove($newLocation)) {
            $player->setLocation($previousLocation);
            $player->setStatus(PlayerStatus::CRASHED);
        } else {
            $this->arena->getTile(...$previousLocation)->setContents($playerTrail);
            $this->arena->getTile(...$newLocation)->setContents($playerType);
        }
    }

    protected function getPlayerType(Player $player): ContentType
    {
        return $player->getSlot();
    }

    protected function serializePlayers(): array
    {
        return array_values($this->players);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'arenaSize' => $this->arenaSize,
            'tiles' => $this->arena->serialize(),
            'players' => $this->serializePlayers(),
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

    public function isOver(): bool
    {
        return $this->getPlayers()->every(fn (Player $player) => $player->crashed());
    }
}
