<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir\Remote;

use JsonSerializable;

class Key implements JsonSerializable
{
    public function __construct(
        private ?string $name = null,
        private int $width = 1,
        private int $height = 1,
        private int $top = 0,
        private int $left = 0,
        private int $style = 0,
        private ?string $background = null,
        private ?string $docked = null,
        private ?int $eventId = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function getStyle(): int
    {
        return $this->style;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function getDocked(): ?string
    {
        return $this->docked;
    }

    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'top' => $this->getTop(),
            'left' => $this->getLeft(),
            'style' => $this->getStyle(),
            'background' => $this->getBackground(),
            'docked' => $this->getDocked(),
            'eventId' => $this->getEventId(),
        ];
    }
}
