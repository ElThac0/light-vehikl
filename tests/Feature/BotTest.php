<?php

namespace Tests\Feature;

use App\Enums\Direction;
use App\GameObjects\Player;
use Tests\TestCase;
use App\GameObjects\Bot;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;

class BotTest extends TestCase
{
    function testItCreatePlayerForBot(): void
    {
        Event::fake();

        $arenaSize = 2;
        $gameState = new GameState($arenaSize);

        $this->assertEquals(0, $gameState->getPlayers()->count());

        $bot = new Bot();

        $gameState->addBot($bot);

        $this->assertEquals(1, $gameState->getPlayers()->count());

        $gameBot = $gameState->getPlayers()->first();

        $this->assertEquals(Player::class, $gameBot::class);
        $this->assertSame($bot->getPlayer(), $gameBot);

        $gameState->nextTick();
        $this->assertEquals(Direction::WEST, $bot->getPlayer()->direction);
    }

    function testItSavesAndAssociatesBots(): void
    {
        Event::fake();

        $arenaSize = 2;
        $gameState = new GameState($arenaSize);

        $this->assertEquals(0, $gameState->getPlayers()->count());

        $bot = new Bot();

        $gameState->addBot($bot);
        $id = $gameState->getId();
        $gameState->save();

        $restored = GameState::find($id);

        $this->assertEquals(1, $gameState->getPlayers()->count());
        $this->assertSame($bot->getPlayer(), $gameState->getPlayers()->first());
    }
}
