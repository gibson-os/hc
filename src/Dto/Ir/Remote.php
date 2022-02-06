<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir;

use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Dto\Ir\Remote\Key;
use GibsonOS\Module\Hc\Model\Module;
use JsonSerializable;

class Remote implements JsonSerializable, AttributeInterface
{
    /**
     * @param Key[] $keys
     */
    public function __construct(
        #[IsAttribute] private ?string $name = null,
        private ?int $id = null,
        #[IsAttribute] private array $keys = [],
        #[IsAttribute] private ?string $background = null
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
            'background' => $this->getBackground(),
            'keys' => $this->getKeys(),
            'width' => max(array_map(static fn (Key $key): int => $key->getWidth() + $key->getLeft(), $this->getKeys()) ?: [0]),
            'height' => max(array_map(static fn (Key $key): int => $key->getHeight() + $key->getTop(), $this->getKeys()) ?: [0]),
        ];
    }

    public function getSubId(): ?int
    {
        return $this->getId();
    }

    public function getTypeName(): string
    {
        return 'ir';
    }

    public function getModule(): ?Module
    {
        return null;
    }
}
