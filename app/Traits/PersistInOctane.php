<?php

namespace App\Traits;

use Vehikl\LvObjects\ContentType;
use App\GameObjects\Bot;
use App\GameObjects\GameState;
use Vehikl\LvObjects\GameObjects\Player;
use Vehikl\LvObjects\GameObjects\Tile;
use Laravel\Octane\Facades\Octane;

trait PersistInOctane
{

    protected string $id;
    protected int $arenaSize;
    public array $arena;
    protected array $players = [];
    protected array $bots = [];

    abstract protected function serializeArena();

    public function playersToString(): string
    {
        return json_encode($this->serializePlayers());
    }

    protected function deserializePlayers(array $playerArrays): array
    {
        $players = array_map(fn ($arr) => Player::deserialize($arr), $playerArrays);

        return array_reduce($players, function (array $carry, Player $player) {
            $carry[$player->getSlot()->value] = $player;
            return $carry;
        }, []);
    }

    protected function deserializeBots(array $playerArrays): array
    {
        $bots = array_map(fn ($arr) => Bot::deserialize($arr), $playerArrays);

        return array_reduce($bots, function (array $carry, Bot $bot) {
            $carry[] = $bot;
            return $carry;
        }, []);
    }

    public static function find($id): ?GameState
    {
        $dehydrated = Octane::table('gameState')->get($id);
        return $dehydrated ? GameState::hydrate($dehydrated, $id) : null;
    }

    public function save(): static
    {
        Octane::table('gameState')->set($this->id, [
            'id' => $this->id,
            'arenaSize' => $this->arenaSize,
            'arena' => join($this->serializeArena()),
            'players' => $this->playersToString(),
            'bots' => json_encode(array_keys($this->bots)),
        ]);
        logger()->info(json_encode(array_keys($this->bots)));
        return $this;
    }

    public static function hydrate(array $data, string $id): GameState
    {
        $gameState = new GameState($data['arenaSize'], $id);
        $gameState->arenaFromString($data['arena']);
        $gameState->playersFromString($data['players']);
        $gameState->hydrateBots(json_decode($data['bots'] ?? []));
        return $gameState;
    }

    protected function hydrateBots(array $locations)
    {
        // for each key (bot location)
        // create a bot from that player and push into bot array

        foreach ($locations as $playerSlot => $index) {
            $this->bots[] = Bot::fromPlayer($this->players[$playerSlot]);
        }
    }

    public function playersFromString(string $input): void
    {
        $this->players = $this->deserializePlayers(json_decode($input, true));
    }

    public function arenaFromString(string $input): void
    {
        $tileBits = str_split($input);
        $this->arena = array_map(
            function ($tile, $key) {
                [$x, $y] = $this->keyToXY($key);
                return new Tile($x, $y, ContentType::from($tile));
            },
            $tileBits,
            array_keys($tileBits)
        );
    }
}
