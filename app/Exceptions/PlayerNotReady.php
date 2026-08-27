<?php

namespace App\Exceptions;

class PlayerNotReady extends GameException
{
    public function __construct()
    {
        parent::__construct('Player is not waiting to be marked ready.');
    }
}
