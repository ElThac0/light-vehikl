<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Laravel\Reverb\Events\ChannelRemoved;
use Laravel\Reverb\Events\MessageSent;

/**
 * Runs inside the Reverb server. When someone drops off a game's presence
 * channel, hand off to a separate process to deal with it - doing the work
 * here would block the server, and broadcasting from inside it would wait
 * on itself.
 */
class CheckGamePresenceOnDisconnect
{
    protected const CHANNEL_PREFIX = 'presence-game.';

    /**
     * The last connection left the channel.
     */
    public function handleChannelRemoved(ChannelRemoved $event): void
    {
        $this->check($event->channel->name());
    }

    /**
     * Reverb has no server-side event for a single member leaving, but it
     * tells the remaining members, so watch for that message going out.
     */
    public function handleMessageSent(MessageSent $event): void
    {
        // Every message to every connection passes through here; bail cheaply.
        if (! str_contains($event->message, 'pusher_internal:member_removed')) {
            return;
        }

        $this->check(json_decode($event->message, true)['channel'] ?? '');
    }

    protected function check(string $channel): void
    {
        if (! Str::startsWith($channel, self::CHANNEL_PREFIX)) {
            return;
        }

        $gameId = Str::after($channel, self::CHANNEL_PREFIX);

        // Backgrounded so the shell (and this call) returns immediately.
        Process::path(base_path())->run(
            sprintf('%s artisan game:check-presence %s > /dev/null 2>&1 &', escapeshellarg(PHP_BINARY), escapeshellarg($gameId))
        );
    }
}
