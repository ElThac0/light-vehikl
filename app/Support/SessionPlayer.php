<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * Players aren't user accounts, so each session gets a random player id.
 * It's safe to share with other players, unlike the session id itself,
 * which is the value of the session cookie.
 */
class SessionPlayer
{
    public static function id(Session $session): string
    {
        if (! $session->has('player_id')) {
            $session->put('player_id', Str::uuid()->toString());
        }

        return $session->get('player_id');
    }
}
