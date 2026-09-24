<?php

namespace Tests\Feature;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LightVehikl\LvObjects\GameObjects\Player;
use Tests\TestCase;

/**
 * The session id is the session cookie's value, so anyone who learns it can
 * take over that session. It must never reach other players.
 */
class SessionIdPrivacyTest extends TestCase
{
    protected string $sessionId;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        config(['broadcasting.default' => 'reverb']);

        $this->sessionId = Str::random(40);
        $this->withCredentials()->withCookie(config('session.cookie'), $this->sessionId);
    }

    public function test_creating_a_game_does_not_expose_the_session_id(): void
    {
        $response = $this->postJson(route('game.create'));

        $this->assertStringNotContainsString($this->sessionId, $response->getContent());
        $this->assertStringNotContainsString($this->sessionId, json_encode(GameState::find($response->json('id'))->toArray()));
    }

    public function test_joining_a_game_does_not_expose_the_session_id(): void
    {
        $game = new GameState(5);
        $game->addPlayer(new Player('someone-else'));
        $game->save();

        $response = $this->postJson(route('game.join', $game->getId()))->assertOk();

        $this->assertStringNotContainsString($this->sessionId, $response->getContent());
    }

    public function test_the_presence_channel_does_not_expose_the_session_id(): void
    {
        $id = $this->postJson(route('game.create'))->json('id');

        $response = $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'presence-game.'.$id,
        ])->assertOk();

        $this->assertStringNotContainsString($this->sessionId, $response->json('channel_data'));
    }

    public function test_the_player_keeps_the_same_id_across_requests(): void
    {
        $id = $this->postJson(route('game.create'))->json('id');
        $playerId = GameState::find($id)->getPlayers()->first()->getId();

        $this->postJson(route('game.mark-ready', $id))->assertOk();
        $this->postJson(route('game.join', $id))->assertOk();

        $this->assertCount(1, GameState::find($id)->getPlayers());
        $this->assertSame($playerId, GameState::find($id)->getPlayers()->first()->getId());
    }
}
