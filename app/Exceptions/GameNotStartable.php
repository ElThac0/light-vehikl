<?php

namespace App\Exceptions;

class GameNotStartable extends GameException
{
    public static function notEnoughPlayers(): self
    {
        return new self('Not enough players.');
    }

    public static function notEveryoneReady(): self
    {
        return new self('Not everyone is ready.');
    }
}
