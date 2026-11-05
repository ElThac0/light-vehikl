<?php

namespace Tests\Feature;

use App\Events\GameCreated;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateGameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
    }

    public function test_the_creator_joins_the_game_they_create(): void
    {
        $id = $this->postJson(route('game.create'))
            ->assertOk()
            ->assertJsonCount(1, 'players')
            ->json('id');

        $this->assertCount(1, GameState::find($id)->getPlayers());
        $this->getJson(route('game.my'))->assertJsonPath('id', $id);
        Event::assertDispatched(GameCreated::class);
    }

    public function test_a_spectating_creator_leaves_the_game_empty_for_others_to_join(): void
    {
        $id = $this->postJson(route('game.create'), ['spectate' => true])
            ->assertOk()
            ->assertJsonCount(0, 'players')
            ->json('id');

        $this->assertCount(0, GameState::find($id)->getPlayers());
        $this->assertContains($id, GameState::list());
        $this->getJson(route('game.my'))->assertExactJson([]);
        Event::assertDispatched(GameCreated::class);

        GameState::mutate($id, fn (GameState $game) => $game->join('someone-else'));

        $this->assertCount(1, GameState::find($id)->getPlayers());
    }

    public function test_the_game_list_shows_who_created_each_game(): void
    {
        $id = $this->withSession(['player_name' => 'Ada'])
            ->postJson(route('game.create'), ['spectate' => true])
            ->json('id');

        $this->assertSame('Ada', GameState::find($id)->getCreatedBy());

        $this->getJson(route('game.list'))
            ->assertOk()
            ->assertExactJson([['id' => $id, 'createdBy' => 'Ada']]);
    }

    public function test_the_game_list_skips_games_whose_state_is_gone(): void
    {
        $game = new GameState(5);
        $game->track();

        $this->getJson(route('game.list'))->assertExactJson([]);
    }
}
