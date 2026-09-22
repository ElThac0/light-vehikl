<?php

namespace Tests\Unit\GameState;

use App\Events\GameEnded;
use App\Events\GameUpdated;
use App\Exceptions\PlayerNotInGame;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use LightVehikl\LvObjects\Enums\ContentType;
use LightVehikl\LvObjects\Enums\GameStatus;
use LightVehikl\LvObjects\Enums\PlayerStatus;
use LightVehikl\LvObjects\GameObjects\Bot;
use LightVehikl\LvObjects\GameObjects\Player;
use LightVehikl\LvObjects\GameObjects\Tile;

class GameStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    public function testInitializes(): void
    {
        $gameState = new GameState(5);

        $this->assertCount(25, $gameState->toArray()['tiles']);
        $this->assertIsInt($gameState->getMaxPlayers());
    }

    #[DataProvider('tileCoordinates')]
    public function testItCanAccessATile($x, $y): void
    {
        $gameState = new GameState(5);

        $tile = $gameState->arena->getTile($x, $y);

        $this->assertInstanceOf(Tile::class, $tile);
        $this->assertEquals($x, $tile->getX());
        $this->assertEquals($y, $tile->getY());
    }

    public function testItGetsStartLocations(): void
    {
        $gameState = new GameState(5);

        $startLocations = $gameState->arena->getStartLocations();

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
                    $gameState->addPlayer(new Player('fakeId'));
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

    public function testItEndsAndLeavesTheGameListWhenOnePlayerRemains(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('abc321'));
        $gameState->track();
        $gameState->nextTick();

        $this->assertTrue($gameState->isOver());
        $this->assertNotContains($gameState->getId(), GameState::list());
        Event::assertDispatched(GameEnded::class, 1);
    }

    public function testItOnlyEndsOnce(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('abc321'));

        $gameState->nextTick();
        $gameState->nextTick();

        Event::assertDispatchedTimes(GameEnded::class, 1);
    }

    public function testItDoesNotEndWhileTwoPlayersAreAlive(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('abc321'));
        $gameState->addPlayer(new Player('taco'));
        $gameState->track();

        $gameState->nextTick();

        $this->assertFalse($gameState->isOver());
        $this->assertContains($gameState->getId(), GameState::list());
        Event::assertNotDispatched(GameEnded::class);
    }

    public function testItMarksBotsInTheSerializedPlayers(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('human'));
        $gameState->addBot(new Bot);

        $players = collect($gameState->toArray()['players'])->keyBy('id');

        $this->assertFalse($players['human']['isBot']);
        $this->assertTrue($players->except('human')->first()['isBot']);
    }

    public function testLeavingBeforeTheGameStartsFreesTheSlot(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('first'));
        $gameState->addPlayer(new Player('second'));

        $gameState->leave('first');
        $gameState->addPlayer(new Player('third'));

        $this->assertNull($gameState->findPlayer('first'));
        $this->assertEquals(ContentType::PLAYER1, $gameState->findPlayer('third')->getSlot());
        $this->assertEquals(ContentType::PLAYER2, $gameState->findPlayer('second')->getSlot());
    }

    public function testLeavingARunningGameCrashesThePlayer(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('first'));
        $gameState->addPlayer(new Player('second'));
        $gameState->setStatus(GameStatus::ACTIVE);

        $gameState->leave('first');

        $this->assertEquals(PlayerStatus::CRASHED, $gameState->findPlayer('first')->getStatus());
        $this->assertTrue($gameState->hasHumanPlayers());
    }

    public function testItHasNoHumanPlayersOnceOnlyBotsRemain(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('human'));
        $gameState->addBot(new Bot);

        $this->assertTrue($gameState->hasHumanPlayers());

        $gameState->leave('human');

        $this->assertFalse($gameState->hasHumanPlayers());
    }

    public function testItHasNoHumanPlayersOnceEveryoneLeavesARunningGame(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('human'));
        $gameState->addBot(new Bot);
        $gameState->setStatus(GameStatus::ACTIVE);

        $gameState->leave('human');

        $this->assertFalse($gameState->hasHumanPlayers());
    }

    public function testLeavingAGameYouAreNotInThrows(): void
    {
        $gameState = new GameState(5);

        $this->expectException(PlayerNotInGame::class);

        $gameState->leave('nobody');
    }

    public function testDisconnectingRemovesOnlyPresenceTrackedPlayers(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('browser'));
        $gameState->addPlayer(new Player('remote'));
        $gameState->trackPresence('browser');

        $gameState->disconnectAbsentPlayers([]);

        $this->assertNull($gameState->findPlayer('browser'));
        $this->assertNotNull($gameState->findPlayer('remote'));
    }

    public function testConnectedPlayersStayInTheGame(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('browser'));
        $gameState->trackPresence('browser');

        $gameState->disconnectAbsentPlayers(['browser']);

        $this->assertNotNull($gameState->findPlayer('browser'));
    }

    public function testDisconnectingFromARunningGameLeavesOnlyBots(): void
    {
        $gameState = new GameState(5);
        $gameState->addPlayer(new Player('browser'));
        $gameState->addBot(new Bot);
        $gameState->trackPresence('browser');
        $gameState->setStatus(GameStatus::ACTIVE);

        $gameState->disconnectAbsentPlayers([]);

        $this->assertFalse($gameState->hasHumanPlayers());
    }

    public function testTrackingPresenceForSomeoneNotInTheGameThrows(): void
    {
        $gameState = new GameState(5);

        $this->expectException(PlayerNotInGame::class);

        $gameState->trackPresence('stranger');
    }
}
