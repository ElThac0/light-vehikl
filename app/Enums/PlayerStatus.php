<?php

namespace App\Enums;
enum PlayerStatus: string
{
    case WAITING = 'waiting';
    case READY = 'ready';
    case ACTIVE = 'active';
    case CRASHED = 'crashed';
}
