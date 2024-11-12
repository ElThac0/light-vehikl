<?php

namespace App\Enums;

enum ContentType: int
{
    case EMPTY = 0;
    case WALL = 1;
    case PLAYER1 = 2;
    case PLAYER1_TRAIL = 3;
    case PLAYER2 = 4;
    case PLAYER2_TRAIL = 5;
    case PLAYER3 = 6;
    case PLAYER3_TRAIL = 7;
    case PLAYER4 = 8;
    case PLAYER4_TRAIL = 9;

    public static function playerByNumber(int $number): ?self
    {
        return match ($number) {
            1 => self::PLAYER1,
            2 => self::PLAYER2,
            3 => self::PLAYER3,
            4 => self::PLAYER4,
            default => null
        };
    }

    public function trailType(): ?self
    {
        return match ($this) {
            self::PLAYER1 => self::PLAYER1_TRAIL,
            self::PLAYER2 => self::PLAYER2_TRAIL,
            self::PLAYER3 => self::PLAYER3_TRAIL,
            self::PLAYER4 => self::PLAYER4_TRAIL,
            default => null
        };
    }
}
