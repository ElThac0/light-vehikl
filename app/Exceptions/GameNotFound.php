<?php

namespace App\Exceptions;

class GameNotFound extends GameException
{
    protected int $status = 404;

    public static function for(string $id): self
    {
        return new self("Game [{$id}] not found.");
    }
}
