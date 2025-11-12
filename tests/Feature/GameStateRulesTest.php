<?php

namespace Tests\Feature;

use LightVehikl\LvObjects\Enums\ContentType;
use LightVehikl\LvObjects\Enums\Direction;
use LightVehikl\LvObjects\Enums\PlayerStatus;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use LightVehikl\LvObjects\GameObjects\Player;

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

        $playerPosition = $gameState->getPlayer(ContentType::PLAYER1)->getLocation();
        $this->assertLessThanOrEqual($arenaSize, $playerPosition[0]);
        $this->assertLessThanOrEqual($arenaSize, $playerPosition[1]);
        $this->assertGreaterThanOrEqual(0, $playerPosition[0]);
        $this->assertGreaterThanOrEqual(0, $playerPosition[1]);
        $this->assertEquals(PlayerStatus::CRASHED, $gameState->getPlayer(ContentType::PLAYER1)->getStatus());
    }

    function testItPreventsPlayerMovingIntoOccupiedSquare(): void
    {
        Event::fake();

        $arenaSize = 2;
        $gameState = new GameState($arenaSize);
        $player = new Player('abc321');
        $gameState->addPlayer($player);
        $player->setDirection(Direction::EAST);

        $nextTile = $gameState->arena->getTile(1,0);
        $nextTile->setContents(ContentType::WALL);

        $gameState->nextTick();

        $this->assertEquals(PlayerStatus::CRASHED, $gameState->getPlayers()[ContentType::PLAYER1->value]->getStatus());
    }
}
