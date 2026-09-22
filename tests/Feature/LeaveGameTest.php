<?php

namespace Tests\Feature;

use App\Events\GameRemoved;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LightVehikl\LvObjects\GameObjects\Player;
use Tests\TestCase;

class LeaveGameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        // Keep one session (and so one player id) across requests.
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
    }

    public function testTheGameIsRemovedWhenTheLastHumanLeaves(): void
    {
        $id = $this->postJson(route('game.create'))->json('id');
        $this->postJson(route('game.add-bot', $id));

        $this->postJson(route('game.leave', $id))->assertOk();

        $this->assertNull(GameState::find($id));
        $this->assertNotContains($id, GameState::list());
        Event::assertDispatched(GameRemoved::class);
    }

    public function testTheGameStaysWhileAHumanRemains(): void
    {
        $game = new GameState(5);
        $game->addPlayer(new Player('someone-else'));
        $game->save();
        $game->track();

        $this->postJson(route('game.join', $game->getId()))->assertOk();
        $this->assertCount(2, GameState::find($game->getId())->getPlayers());

        $this->postJson(route('game.leave', $game->getId()))->assertOk();

        $remaining = GameState::find($game->getId());
        $this->assertNotNull($remaining);
        $this->assertCount(1, $remaining->getPlayers());
        $this->assertContains($game->getId(), GameState::list());
        Event::assertNotDispatched(GameRemoved::class);
    }
}
