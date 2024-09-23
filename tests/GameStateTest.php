<?php

namespace Tests;

use App\GameState;
use App\Models\Player;
use App\Tile;
use Tests\TestCase;

class GameStateTest extends TestCase
{
    /**  */
    public function testInitializes(): void
    {
        $gameState = new GameState(5);

        $this->assertInstanceOf(GameState::class, $gameState);
        $this->assertCount(25, $gameState->toArray());
    }

    /**
     * @dataProvider tileCoordinates
     */
    public function testItCanAccessATile($x, $y): void
    {
        $gameState = new GameState(5);

        $tile = $gameState->getTile($x, $y);

        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertEquals($x, $tile->getX());
        $this->assertEquals($y, $tile->getY());
    }

    public function testItCanAddAPlayer(): void
    {
        $gameState = new GameState(5);

        $player = Player::factory()->make();

        $gameState->addPlayer($player);

        $this->assertCount(1, $gameState->getPlayers());
        $player = $gameState->getPlayers()[0];
        $this->assertInstanceOf(Player::class, $player);
    }

    public function testItSetsThePlayersStartLocationWhenAddingAPlayer(): void
    {
        $gameState = new GameState(5);

        $player = new Player();

        $gameState->addPlayer($player);
        $player = $gameState->getPlayers()[0];

        // [3, 4]
        $this->assertIsArray($player->getLocation());
        $this->assertIsInt($player->getLocation()[0]);
        $this->assertIsInt($player->getLocation()[1]);
    }

    public function tileCoordinates(): array
    {
        return [
            [
                'x' => 1,
                'y' => 1
            ],
            [
                'x' => 3,
                'y' => 1
            ],
            [
                'x' => 4,
                'y' => 4
            ],
        ];
    }
}
