<?php

namespace App\GameObjects;

use App\Events\GameUpdated;
use App\Exceptions\GameAlreadyStarted;
use App\Exceptions\GameFull;
use App\Exceptions\GameNotStartable;
use App\Exceptions\GameOver;
use App\Exceptions\PlayerAlreadyInGame;
use App\Exceptions\PlayerNotInGame;
use App\Exceptions\PlayerNotReady;
use App\Traits\PersistInCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use LightVehikl\LvObjects\Enums\ContentType;
use LightVehikl\LvObjects\Enums\Direction;
use LightVehikl\LvObjects\Enums\GameStatus;
use LightVehikl\LvObjects\Enums\PlayerStatus;
use LightVehikl\LvObjects\GameObjects\Arena;
use LightVehikl\LvObjects\GameObjects\Bot;
use LightVehikl\LvObjects\GameObjects\Player;
use LightVehikl\LvObjects\GameObjects\StartLocation;
use Ramsey\Uuid\Uuid;

class GameState
{
    use PersistInCache;

    const MAX_PLAYERS = 4;

    protected string $id;

    public Arena $arena;

    protected array $players = [];

    /** @var array<Bot> */
    protected array $bots = [];

    protected int $maxX;

    protected int $maxY;

    protected GameStatus $status = GameStatus::WAITING;

    protected int $tick = 0;

    public Carbon $createdAt;

    public function __construct(protected int $arenaSize, $id = null)
    {
        $this->arena = new Arena($arenaSize);
        $this->maxX = $this->maxY = $this->arenaSize - 1;
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->createdAt = Carbon::now();
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

    public function findPlayer(string $playerId): ?Player
    {
        return $this->getPlayers()->first(fn (Player $player) => $player->getId() === $playerId);
    }

    /** @throws PlayerNotInGame|PlayerNotReady */
    public function setReady(string $playerId): self
    {
        $player = $this->findPlayer($playerId);

        if ($player === null) {
            throw new PlayerNotInGame;
        }

        if ($player->status !== PlayerStatus::WAITING) {
            throw new PlayerNotReady;
        }

        $player->setStatus(PlayerStatus::READY);

        GameUpdated::dispatch($this);

        return $this;
    }

    /** @throws PlayerNotInGame */
    public function setPlayerDirection(string $playerId, Direction $direction): void
    {
        $player = $this->findPlayer($playerId) ?? throw new PlayerNotInGame;

        $player->setDirection($direction);
    }

    /**
     * Add the player to the game if they aren't already in it, enforcing the
     * rules that only apply to joining (not to seeding a fresh game).
     *
     * @throws GameOver|GameAlreadyStarted|GameFull|PlayerAlreadyInGame
     */
    public function join(string $playerId): Player
    {
        if ($existing = $this->findPlayer($playerId)) {
            return $existing;
        }

        if ($this->isOver()) {
            throw new GameOver;
        }

        if ($this->isActive()) {
            throw new GameAlreadyStarted;
        }

        $player = new Player($playerId);

        $this->addPlayer($player);

        return $player;
    }

    /** @throws GameNotStartable */
    public function ensureStartable(): void
    {
        if ($this->getPlayers()->count() < 2) {
            throw GameNotStartable::notEnoughPlayers();
        }

        if ($this->getPlayers()->some(fn (Player $player) => $player->status !== PlayerStatus::READY)) {
            throw GameNotStartable::notEveryoneReady();
        }
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
     * @throws GameFull|PlayerAlreadyInGame
     */
    public function addPlayer(Player $player): ContentType
    {
        if (count($this->players) >= self::MAX_PLAYERS) {
            throw new GameFull;
        }

        if ($this->isInGame($player)) {
            throw new PlayerAlreadyInGame;
        }

        $start = $this->getNextStartLocation();
        $coords = $start->tile->getCoords();
        $playerEnum = $start->playerType;
        $player->setSlot($playerEnum);
        $this->players[$playerEnum->value] = $player->setLocation($coords)->setDirection($start->direction);

        $start->tile->setContents($playerEnum);

        GameUpdated::dispatch($this);

        return $playerEnum;
    }

    /**
     * @throws GameFull|PlayerAlreadyInGame
     */
    public function addBot(Bot $bot): void
    {
        $position = $this->addPlayer($bot->getPlayer()->setStatus(PlayerStatus::READY));
        $this->bots[$position->value] = $bot;
    }

    public function nextTick(): void
    {
        foreach ($this->bots as $bot) {
            $bot->arena = $this->arena;
            $bot->updatePlayer();
        }

        foreach ($this->getPlayers() as $playerSlot => $player) {
            if ($player->getStatus() !== PlayerStatus::CRASHED) {
                $this->movePlayer($player);
            }
        }
        $this->tick++;
        if ($this->shouldEnd()) {
            $this->setStatus(GameStatus::COMPLETE);
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

        if (! $this->arena->validMove($newLocation)) {
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
            'status' => $this->status,
            'tick' => $this->tick,
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

    public function shouldEnd(): bool
    {
        $crashedPlayers = $this->getPlayers()->filter(fn (Player $player) => $player->crashed());

        return $this->getPlayers()->count() - $crashedPlayers->count() <= 1;
    }

    public function isActive(): bool
    {
        return $this->status === GameStatus::ACTIVE;
    }

    public function isOver(): bool
    {
        return $this->status === GameStatus::COMPLETE;
    }

    public function setStatus(GameStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
