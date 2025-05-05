<?php

namespace App\Enums;

enum GameStatus: string
{
    case WAITING = 'waiting';
    case ACTIVE = 'active';
    case COMPLETE = 'complete';
}
