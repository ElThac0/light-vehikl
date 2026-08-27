<?php

namespace App\Exceptions;

class GameFull extends GameException
{
    public function __construct()
    {
        parent::__construct('Game is full.');
    }
}
