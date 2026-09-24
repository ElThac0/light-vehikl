<?php

namespace Tests\Feature;

use App\GameObjects\GameState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LightVehikl\LvObjects\Enums\PersonalityType;
use LightVehikl\LvObjects\GameObjects\Bot;
use LightVehikl\LvObjects\GameObjects\Player;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlayerNameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        // Keep one session (and so one player id) across requests.
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
    }

    public function test_the_chosen_name_is_used_when_creating_a_game(): void
    {
        $this->postJson(route('player.name'), ['name' => 'Flynn'])->assertOk();

        $players = $this->postJson(route('game.create'))->json('players');

        $this->assertSame('Flynn', $players[0]['name']);
    }

    public function test_the_chosen_name_is_used_when_joining_a_game(): void
    {
        $game = new GameState(5);
        $game->addPlayer(new Player('someone-else'));
        $game->save();

        $this->postJson(route('player.name'), ['name' => 'Quorra'])->assertOk();
        $players = $this->postJson(route('game.join', $game->getId()))->json('gameState.players');

        $this->assertSame(['Player 1', 'Quorra'], array_column($players, 'name'));
    }

    public function test_players_without_a_name_are_not_shown_their_id(): void
    {
        $game = new GameState(5);
        $game->addPlayer(new Player('secret-session-id'));
        $game->addBot(new Bot(null, PersonalityType::KEEP_LANE));

        $names = array_column($game->toArray()['players'], 'name');

        $this->assertSame(['Player 1', 'Bot 2'], $names);
    }

    #[DataProvider('rejectedNames')]
    public function test_inappropriate_or_malformed_names_are_rejected(string $name): void
    {
        $this->postJson(route('player.name'), ['name' => $name])->assertJsonValidationErrors('name');

        $this->assertNull(session('player_name'));
    }

    public static function rejectedNames(): array
    {
        return [
            'empty' => [''],
            'too short' => ['A'],
            'too long' => [str_repeat('a', 21)],
            'markup' => ['<b>Flynn</b>'],
            'plain' => ['shit'],
            'inside a word' => ['BigFuckingTron'],
            'digit swaps' => ['Sh1t'],
            'stretched' => ['fuuuuck'],
            'separated' => ['f.u.c.k'],
            'whole word' => ['Kick Ass'],
        ];
    }

    #[DataProvider('acceptedNames')]
    public function test_innocent_names_containing_bad_words_are_allowed(string $name): void
    {
        $this->postJson(route('player.name'), ['name' => $name])->assertOk();
    }

    public static function acceptedNames(): array
    {
        return [['Classic'], ['Cocktail'], ['Grape Ape'], ['Sussex'], ['Analyst'], ['Dickens'], ['Scunthorpe'], ['Niger']];
    }
}
