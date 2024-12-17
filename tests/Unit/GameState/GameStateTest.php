<?php

namespace Tests\Unit\GameState;

use App\Enums\ContentType;
use App\Enums\PlayerStatus;
use App\Events\GameUpdated;
use App\GameObjects\GameState;
use App\GameObjects\Player;
use App\GameObjects\Tile;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameStateTest extends TestCase
{
    public function testInitializes(): void
    {
        $gameState = new GameState(5);

        $this->assertInstanceOf(GameState::class, $gameState);
        $this->assertCount(25, $gameState->toArray()['tiles']);
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

        $this->assertInstanceOf(Tile::class, $startLocations[0]->tile);

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
        $nextLocation = $gameState->getNextStartLocation()->tile;

        $player = new Player('abc123');
        $gameState->addPlayer($player);

        $this->assertCount(1, $gameState->getPlayers());
        $player = $gameState->getPlayer(ContentType::PLAYER1);
        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals($nextLocation->getCoords(), $player->getLocation());
        $this->assertIsInt($player->getLocation()[0]);
        $this->assertIsInt($player->getLocation()[1]);
        $this->assertEquals(PlayerStatus::WAITING, $player->getStatus());
    }

    public function testItCanAddMultiplePlayers(): void
    {
        $gameState = new GameState(5);

        $player1 = new Player('abc123');
        $gameState->addPlayer($player1);

        $player2 = new Player('abc456');
        $gameState->addPlayer($player2);

        $player3 = new Player('abc789');
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

        $player1 = new Player('abc123');
        $gameState->addPlayer($player1);

        $this->assertTrue($nextLocation->tile->isOccupied());
        $this->assertEquals(ContentType::PLAYER1, $nextLocation->tile->getContents());
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

    public function testItUpdatesOnATick(): void
    {
        Event::fake();

        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('abc321'));
        $player1 = $gameState->getPlayer(ContentType::PLAYER1);
        $start = $player1->getLocation();

        $gameState->nextTick();

        $this->assertNotEquals($start, $player1->getLocation());
        Event::assertDispatched(GameUpdated::class);
    }

    public function testDetermineIfThePlayerIsInTheGame(): void
    {
        $gameState = new GameState(5);

        $player1 = new Player('abc123');
        $player2 = new Player('taco');
        $gameState->addPlayer($player1);

        $this->assertTrue($gameState->isInGame($player1));
        $this->assertFalse($gameState->isInGame($player2));
    }

    public function testLoadsArenaFromString(): void
    {
        $gameState = new GameState(2);

        $gameStr = '1111';
        $gameState->arenaFromString($gameStr);

        $this->assertEquals(ContentType::WALL, $gameState->getTile(0,0)->getContents());
        $this->assertEquals(ContentType::WALL, $gameState->getTile(1,1)->getContents());

        $gameStr = '0234';
        $gameState->arenaFromString($gameStr);

        $this->assertEquals(ContentType::EMPTY, $gameState->getTile(0,0)->getContents());
        $this->assertEquals(ContentType::PLAYER1, $gameState->getTile(1,0)->getContents());
    }

    public function testLoadsPlayersFromString(): void
    {
        $gameState = new GameState(2);

        $player1 = new Player('abc123');
        $gameState->addPlayer($player1);

        $playerString = $gameState->playersToString();

        $newGame = new GameState(2);
        $newGame->playersFromString($playerString);

        $this->assertEquals($gameState->getPlayers(), $newGame->getPlayers());
    }
}
