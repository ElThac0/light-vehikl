<?php

namespace Tests\Feature;

use App\Events\GameRemoved;
use App\GameObjects\GameState;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Sleep;
use LightVehikl\LvObjects\GameObjects\Bot;
use LightVehikl\LvObjects\GameObjects\Player;
use Pusher\ApiErrorException;
use Tests\TestCase;

class CheckGamePresenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Sleep::fake();
    }

    protected function gameWithPresencePlayer(): GameState
    {
        $game = new GameState(5);
        $game->addPlayer(new Player('browser'));
        $game->addBot(new Bot);
        $game->trackPresence('browser');
        $game->save();
        $game->track();

        return $game;
    }

    protected function connected(array $ids): void
    {
        Broadcast::shouldReceive('connection->getPusher->getPresenceUsers')
            ->andReturn((object) ['users' => array_map(fn ($id) => (object) ['id' => $id], $ids)]);
    }

    public function testItRemovesTheGameWhenThePresenceChannelIsGone(): void
    {
        $game = $this->gameWithPresencePlayer();
        Broadcast::shouldReceive('connection->getPusher->getPresenceUsers')
            ->andThrow(new ApiErrorException('Not found', 404));

        $this->artisan('game:check-presence', ['gameId' => $game->getId()])->assertSuccessful();

        $this->assertNull(GameState::find($game->getId()));
        Event::assertDispatched(GameRemoved::class);
    }

    public function testItKeepsTheGameWhenThePlayerReconnected(): void
    {
        $game = $this->gameWithPresencePlayer();
        $this->connected(['browser']);

        $this->artisan('game:check-presence', ['gameId' => $game->getId()])->assertSuccessful();

        $this->assertNotNull(GameState::find($game->getId())->findPlayer('browser'));
        Event::assertNotDispatched(GameRemoved::class);
    }

    public function testItKeepsTheGameWhileAnotherHumanIsConnected(): void
    {
        $game = $this->gameWithPresencePlayer();
        GameState::mutate($game->getId(), function (GameState $game) {
            $game->addPlayer(new Player('other'));
            $game->trackPresence('other');
        });
        $this->connected(['other']);

        $this->artisan('game:check-presence', ['gameId' => $game->getId()])->assertSuccessful();

        $remaining = GameState::find($game->getId());
        $this->assertNull($remaining->findPlayer('browser'));
        $this->assertNotNull($remaining->findPlayer('other'));
        Event::assertNotDispatched(GameRemoved::class);
    }

    public function testItWaitsBeforeChecking(): void
    {
        $game = $this->gameWithPresencePlayer();
        $this->connected(['browser']);

        $this->artisan('game:check-presence', ['gameId' => $game->getId(), '--grace' => 3]);

        Sleep::assertSleptTimes(1);
        Sleep::assertSequence([Sleep::for(3)->seconds()]);
    }
}
