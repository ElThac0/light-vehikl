<?php

namespace Tests\Feature;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class GamePresenceChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        config(['broadcasting.default' => 'reverb']);

        // Keep one session (and so one player id) across requests.
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
    }

    protected function authorize(string $gameId)
    {
        return $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'presence-game.'.$gameId,
        ]);
    }

    public function testAPlayerInTheGameCanJoinItsPresenceChannel(): void
    {
        $id = $this->postJson(route('game.create'))->json('id');

        $this->authorize($id)->assertOk()->assertJsonStructure(['auth', 'channel_data']);

        // Now tracked, so dropping off the channel removes them.
        $game = GameState::find($id);
        $game->disconnectAbsentPlayers([]);
        $this->assertFalse($game->hasHumanPlayers());
    }

    public function testSomeoneNotInTheGameIsRejected(): void
    {
        $game = new GameState(5);
        $game->save();

        $this->authorize($game->getId())->assertForbidden();
    }
}
