<?php

namespace App\GameObjects;

use App\Events\GameEnded;
use App\Events\GameRemoved;
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

    /** @var array<string> Ids of players who left after the game started. */
    protected array $departed = [];

    /** @var array<string> Ids of players who have joined the game's presence channel. */
    protected array $presenceTracked = [];

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
        return collect($this->arena->getStartLocations())
            ->first(fn (StartLocation $start) => ! isset($this->players[$start->playerType->value]));
    }

    /**
     * @return Collection<int, Player>
     */
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
    public function join(string $playerId, ?string $name = null): Player
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

        if ($name !== null) {
            $player->setName($name);
        }

        $this->addPlayer($player);

        return $player;
    }

    /**
     * Take a player out of the game. Before the game starts they give up
     * their slot; once it's running they stay on the board as crashed.
     *
     * @throws PlayerNotInGame
     */
    public function leave(string $playerId): void
    {
        $player = $this->findPlayer($playerId) ?? throw new PlayerNotInGame;

        if ($this->hasLeft($playerId)) {
            return;
        }

        if ($this->status === GameStatus::WAITING) {
            $this->arena->getTile(...$player->getLocation())->setContents(ContentType::EMPTY);
            unset($this->players[$player->getSlot()->value]);
            $this->presenceTracked = array_values(array_diff($this->presenceTracked, [$playerId]));
        } else {
            if ($this->isActive()) {
                $player->setStatus(PlayerStatus::CRASHED);
            }

            $this->departed[] = $playerId;
        }

        GameUpdated::dispatch($this);
    }

    /**
     * Whether anyone other than a server-side bot is still in the game.
     */
    public function hasHumanPlayers(): bool
    {
        return $this->getHumanPlayers()->isNotEmpty();
    }

    /**
     * Players who aren't server-side bots and haven't left.
     *
     * @return Collection<int, Player>
     */
    public function getHumanPlayers(): Collection
    {
        return $this->getPlayers()->filter(fn (Player $player, int $slot) => ! isset($this->bots[$slot])
            && ! $this->hasLeft($player->getId()));
    }

    protected function hasLeft(string $playerId): bool
    {
        return in_array($playerId, $this->departed, true);
    }

    /**
     * Note that a player has joined the presence channel, so from now on
     * their disconnecting from it counts as leaving. Players who never join
     * it (e.g. remote clients on the public channel) are never removed this way.
     *
     * @throws PlayerNotInGame
     */
    public function trackPresence(string $playerId): void
    {
        $this->findPlayer($playerId) ?? throw new PlayerNotInGame;

        $this->presenceTracked = array_values(array_unique([...$this->presenceTracked, $playerId]));
    }

    /**
     * Remove every presence-tracked human who isn't among the ids currently
     * connected to the presence channel.
     *
     * @param  array<string>  $connectedIds
     */
    public function disconnectAbsentPlayers(array $connectedIds): void
    {
        $this->getHumanPlayers()
            ->filter(fn (Player $player) => in_array($player->getId(), $this->presenceTracked, true)
                && ! in_array($player->getId(), $connectedIds, true))
            ->each(fn (Player $player) => $this->leave($player->getId()));
    }

    /**
     * Delete the game once no humans are left in it, letting lobbies know.
     */
    public static function removeIfAbandoned(GameState $game): bool
    {
        if ($game->hasHumanPlayers()) {
            return false;
        }

        static::forget($game->getId());

        GameRemoved::dispatch($game);

        return true;
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

        // Never fall back to showing the id, which for humans is their session id.
        if (! $this->hasName($player)) {
            $player->setName('Player '.$this->slotNumber($playerEnum));
        }

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
        $player = $bot->getPlayer();
        $named = $this->hasName($player);

        $position = $this->addPlayer($player->setStatus(PlayerStatus::READY));
        $this->bots[$position->value] = $bot;

        if (! $named) {
            $player->setName('Bot '.$this->slotNumber($position));
        }
    }

    /**
     * Player falls back to its id when no name has been set.
     */
    protected function hasName(Player $player): bool
    {
        return $player->getName() !== $player->getId();
    }

    protected function slotNumber(ContentType $slot): int
    {
        return intdiv($slot->value, 2);
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
        if (! $this->isOver() && $this->shouldEnd()) {
            $this->end();
        }

        GameUpdated::dispatch($this);
    }

    /**
     * Mark the game complete and drop it from the game list, so it stops
     * ticking and lobby clients stop offering it.
     */
    public function end(): void
    {
        $this->setStatus(GameStatus::COMPLETE);
        $this->untrack();

        GameEnded::dispatch($this);
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
        return collect($this->players)
            ->map(fn (Player $player, int $slot) => [
                ...$player->jsonSerialize(),
                'name' => $player->getName(),
                'isBot' => isset($this->bots[$slot]),
            ])
            ->values()
            ->all();
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
