<?php

namespace Tests\Feature;

use App\Enums\Direction;
use App\GameState;
use App\Models\Player;
use App\PlayerStatus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameStateRulesTest extends TestCase
{
    function testItPreventsPlayerOutOfBounds(): void
    {
        Event::fake();

        $arenaSize = 2;
        $gameState = new GameState($arenaSize);
        $gameState->addPlayer(new Player('abc321'));

        for ($x = 0; $x < $arenaSize + 1; $x++) {
            $gameState->nextTick();
        }

        $playerPosition = $gameState->getPlayers()[0]->getLocation();
        $this->assertLessThanOrEqual($arenaSize, $playerPosition[0]);
        $this->assertLessThanOrEqual($arenaSize, $playerPosition[1]);
        $this->assertGreaterThanOrEqual(0, $playerPosition[0]);
        $this->assertGreaterThanOrEqual(0, $playerPosition[1]);
        $this->assertEquals(PlayerStatus::CRASHED, $gameState->getPlayers()[0]->getStatus());
    }

    function testItPreventsPlayerMovingIntoOccupiedSquare(): void
    {
        Event::fake();

        $arenaSize = 2;
        $gameState = new GameState($arenaSize);
        $player = new Player('abc321');
        $gameState->addPlayer($player);
        $player->setDirection(Direction::EAST);

        $nextTile = $gameState->getTile(1,0);
        $nextTile->setContents('wall');

        $gameState->nextTick();

        $this->assertEquals(PlayerStatus::CRASHED, $gameState->getPlayers()[0]->getStatus());
    }
}
