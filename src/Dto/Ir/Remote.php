<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir;

use GibsonOS\Module\Hc\Dto\Ir\Remote\Key;
use JsonSerializable;

class Remote implements JsonSerializable
{
    /**
     * @param Key[] $keys
     */
    public function __construct(
        private ?string $name = null,
        private ?int $id = null,
        private int $itemWidth = 30,
        private array $keys = [],
        private ?string $background = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getItemWidth(): int
    {
        return $this->itemWidth;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'id' => $this->getId(),
            'itemWidth' => $this->getItemWidth(),
            'background' => $this->getBackground(),
            'keys' => $this->getKeys(),
            'width' => max(array_map(static fn (Key $key): int => $key->getWidth() + $key->getLeft(), $this->getKeys()) ?: [0]),
            'height' => max(array_map(static fn (Key $key): int => $key->getHeight() + $key->getTop(), $this->getKeys()) ?: [0]),
        ];
    }
}
