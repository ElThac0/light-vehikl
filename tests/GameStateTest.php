<?php

namespace Tests;

use App\GameState;
use App\Models\Player;
use App\PlayerStatus;
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
        $this->assertIsInt($gameState->getMaxPlayers());
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

    public function testItGetsStartLocations(): void
    {
        $gameState = new GameState(5);

        $startLocations = $gameState->getStartLocations();

        $this->assertInstanceOf(Tile::class, $startLocations[0]);

        $this->assertCount(
            $gameState->getMaxPlayers(),
            $startLocations,
            'Start locations must match max players'
        );

        foreach ($startLocations as $startLocation) {
            $matches = array_filter($startLocations, fn ($i) => $i == $startLocation);
            $this->assertCount(
                1,
                $matches,
                'Start locations must be unique');
        }
    }

    public function testItCanAddAPlayer(): void
    {
        $gameState = new GameState(5);
        $nextLocation = $gameState->getNextStartLocation();

        $player = new Player();
        $gameState->addPlayer($player);

        $this->assertCount(1, $gameState->getPlayers());
        $player = $gameState->getPlayers()[0];
        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals($nextLocation->getCoords(), $player->getLocation());
        $this->assertIsInt($player->getLocation()[0]);
        $this->assertIsInt($player->getLocation()[1]);
        $this->assertEquals(PlayerStatus::WAITING, $player->getStatus());
    }

    public function testItCanAddMultiplePlayers(): void
    {
        $gameState = new GameState(5);

        $player1 = new Player();
        $gameState->addPlayer($player1);

        $player2 = new Player();
        $gameState->addPlayer($player2);

        $player3 = new Player();
        $gameState->addPlayer($player3);

        $this->assertCount(3, $gameState->getPlayers());
    }

    public function testItWillNotAddMoreThanMaxPlayers(): void
    {
        $gameState = new GameState(5);
        $tooManyPlayers = $gameState->getMaxPlayers() + 1;

        $this->assertThrows(
            function () use ($gameState, $tooManyPlayers) {
                for ($i = 0; $i < $tooManyPlayers; $i++) {
                    $gameState->addPlayer(new Player());
                }
            }
        );
    }

    public function testItFindsPlayerInATile(): void
    {
        $gameState = new GameState(5);
        $nextLocation = $gameState->getNextStartLocation();

        $player1 = new Player();
        $gameState->addPlayer($player1);

        $this->assertTrue($nextLocation->isOccupied());
        $this->assertEquals($player1, $nextLocation->getContents());
    }

    public static function tileCoordinates(): array
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