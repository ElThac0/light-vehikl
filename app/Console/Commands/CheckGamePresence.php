<?php

namespace App\Console\Commands;

use App\Exceptions\GameNotFound;
use App\GameObjects\GameState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Sleep;
use Pusher\ApiErrorException;

class CheckGamePresence extends Command
{
    protected $signature = 'game:check-presence {gameId} {--grace=5 : Seconds to wait so a page refresh isn\'t treated as leaving}';

    protected $description = 'Remove players who have disconnected from a game\'s presence channel';

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        Sleep::for((int) $this->option('grace'))->seconds();

        $connectedIds = $this->connectedPlayerIds($gameId);

        try {
            $game = GameState::mutate($gameId, fn (GameState $game) => $game->disconnectAbsentPlayers($connectedIds));
        } catch (GameNotFound) {
            return;
        }

        if (GameState::removeIfAbandoned($game)) {
            $this->info("Removed abandoned game {$gameId}.");
        }
    }

    /**
     * @return array<string>
     */
    protected function connectedPlayerIds(string $gameId): array
    {
        try {
            $users = Broadcast::connection()->getPusher()->getPresenceUsers('presence-game.'.$gameId)->users;
        } catch (ApiErrorException) {
            // Reverb 404s once the channel's last connection is gone.
            return [];
        }

        return collect($users)->map(fn ($user) => (string) $user->id)->all();
    }
}
