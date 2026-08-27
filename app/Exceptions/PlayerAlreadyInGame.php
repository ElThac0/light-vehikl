<?php

namespace App\Exceptions;

class PlayerAlreadyInGame extends GameException
{
    public function __construct()
    {
        parent::__construct('Player is already in this game.');
    }
}
