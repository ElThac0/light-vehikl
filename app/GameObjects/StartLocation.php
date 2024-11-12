<?php
namespace App\GameObjects;

use App\Enums\ContentType;
use App\Enums\Direction;

class StartLocation
{
    public function __construct(
        public ContentType $playerType,
        public Tile $tile,
        public Direction $direction,
    ) {}
}
