<?php

namespace App\Exceptions;

class PlayerNotInGame extends GameException
{
    protected int $status = 404;

    public function __construct()
    {
        parent::__construct('Player is not in this game.');
    }
}
