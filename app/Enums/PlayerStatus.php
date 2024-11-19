<?php

namespace App\Enums;
enum PlayerStatus: string
{
    case WAITING = 'waiting';
    case ACTIVE = 'active';
    case CRASHED = 'crashed';
}
