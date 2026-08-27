<?php

namespace App\Exceptions;

class GameAlreadyStarted extends GameException
{
    protected int $status = 403;

    public function __construct()
    {
        parent::__construct('Game has already started.');
    }
}
