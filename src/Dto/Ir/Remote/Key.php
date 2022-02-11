<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir\Remote;

use GibsonOS\Core\Attribute\ObjectMapper;
use GibsonOS\Module\Hc\Dto\Ir\Key as IrKey;
use JsonSerializable;

class Key implements JsonSerializable
{
    /**
     * @param IrKey[] $keys
     */
    public function __construct(
        private ?string $name = null,
        private int $width = 1,
        private int $height = 1,
        private int $top = 0,
        private int $left = 0,
        private bool $borderTop = true,
        private bool $borderRight = true,
        private bool $borderBottom = true,
        private bool $borderLeft = true,
        private int $borderRadiusTopLeft = 0,
        private int $borderRadiusTopRight = 0,
        private int $borderRadiusBottomLeft = 0,
        private int $borderRadiusBottomRight = 0,
        private ?string $background = null,
        private ?int $eventId = null,
        #[ObjectMapper(IrKey::class)] private array $keys = []
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

    public function hasBorderTop(): bool
    {
        return $this->borderTop;
    }

    public function hasBorderRight(): bool
    {
        return $this->borderRight;
    }

    public function hasBorderBottom(): bool
    {
        return $this->borderBottom;
    }

    public function hasBorderLeft(): bool
    {
        return $this->borderLeft;
    }

    public function getBorderRadiusTopLeft(): int
    {
        return $this->borderRadiusTopLeft;
    }

    public function getBorderRadiusTopRight(): int
    {
        return $this->borderRadiusTopRight;
    }

    public function getBorderRadiusBottomLeft(): int
    {
        return $this->borderRadiusBottomLeft;
    }

    public function getBorderRadiusBottomRight(): int
    {
        return $this->borderRadiusBottomRight;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    /**
     * @return IrKey[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'top' => $this->getTop(),
            'left' => $this->getLeft(),
            'borderTop' => $this->hasBorderTop(),
            'borderRight' => $this->hasBorderRight(),
            'borderBottom' => $this->hasBorderBottom(),
            'borderLeft' => $this->hasBorderLeft(),
            'borderRadiusTopLeft' => $this->getBorderRadiusTopLeft(),
            'borderRadiusTopRight' => $this->getBorderRadiusTopRight(),
            'borderRadiusBottomLeft' => $this->getBorderRadiusBottomLeft(),
            'borderRadiusBottomRight' => $this->getBorderRadiusBottomRight(),
            'background' => $this->getBackground(),
            'eventId' => $this->getEventId(),
            'keys' => $this->getKeys(),
        ];
    }
}
