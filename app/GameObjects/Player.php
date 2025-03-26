<?php

namespace App\GameObjects;


use App\Enums\ContentType;
use App\Enums\Direction;
use App\Enums\PlayerStatus;
use JsonSerializable;

class Player implements JsonSerializable
{
    public function __construct(
        public string $id,
        public PlayerStatus $status = PlayerStatus::WAITING,
        public ?Direction $direction = null,
        public ?ContentType $slot = null,
        public ?int $x = null,
        public ?int $y = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLocation(): array
    {
        return [$this->x, $this->y];
    }

    public function setLocation(array $coordinates): self
    {
        $this->x = $coordinates[0];
        $this->y = $coordinates[1];

        return $this;
    }

    public function setStatus(PlayerStatus $playerStatus): self
    {
        $this->status = $playerStatus;

        return $this;
    }

    public function getStatus(): PlayerStatus
    {
        return $this->status;
    }

    public function setDirection(Direction $direction): Player
    {
        $this->direction = $direction;

        return $this;
    }

    public function moveNorth(): void
    {
        $this->y--;
    }

    public function moveEast(): void
    {
        $this->x++;
    }

    public function moveSouth(): void
    {
        $this->y++;
    }

    public function moveWest(): void
    {
        $this->x--;
    }

    public function setSlot(ContentType $playerEnum)
    {
        $this->slot = $playerEnum;
    }

    public function getSlot(): ContentType
    {
        return $this->slot;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'status' => $this->status->value,
            'id' => $this->id,
            'direction' => $this->direction->value,
            'slot' => $this->slot,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            id: $data['id'],
            status: PlayerStatus::tryFrom($data['status']),
            direction: Direction::tryFrom($data['direction']),
            slot: ContentType::tryFrom($data['slot']),
            x: $data['x'],
            y: $data['y']);
    }

    public function crashed(): bool
    {
        return $this->status === PlayerStatus::CRASHED;
    }

    public function avoidDirections(): array
    {
        $avoid = [];

        if ($this->x === 0) {
            $avoid[] = Direction::WEST;
        }

        if ($this->y === 0) {
            $avoid[] = Direction::NORTH;
        }

        // TODO: Remove magic number 24s
        if ($this->x === 24) {
            $avoid[] = Direction::EAST;
        }

        if ($this->y === 24) {
            $avoid[] = Direction::SOUTH;
        }

        return $avoid;
    }
}
