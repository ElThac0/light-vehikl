<?php

namespace App\Enums;

enum PersonalityType: string
{
    case KEEP_LANE = \App\GameObjects\Personalities\KeepLane::class;
    case CHANGE_DIRECTION = \App\GameObjects\Personalities\ChangeDirection::class;
}
