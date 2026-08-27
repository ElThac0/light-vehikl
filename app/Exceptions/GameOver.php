<?php

namespace App\Exceptions;

class GameOver extends GameException
{
    protected int $status = 410;

    public function __construct()
    {
        parent::__construct('Game is over.');
    }
}
