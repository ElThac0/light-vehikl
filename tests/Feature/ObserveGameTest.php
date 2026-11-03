<?php

namespace Tests\Feature;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LightVehikl\LvObjects\GameObjects\Player;
use Tests\TestCase;

class ObserveGameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
    }

    public function test_an_observer_gets_the_game_without_joining_it(): void
    {
        $game = new GameState(5);
        $game->addPlayer(new Player('someone-else'));
        $game->save();
        $game->track();

        $this->getJson(route('game.observe', $game->getId()))
            ->assertOk()
            ->assertJsonPath('gameState.id', $game->getId())
            ->assertJsonPath('channel', 'GameChannel-'.$game->getId())
            ->assertJsonStructure(['gameState', 'channel', 'webSocketKey']);

        $this->assertCount(1, GameState::find($game->getId())->getPlayers());
        $this->getJson(route('game.my'))->assertExactJson([]);
    }

    public function test_observing_a_missing_game_is_not_found(): void
    {
        $this->getJson(route('game.observe', 'nope'))->assertNotFound();
    }
}
