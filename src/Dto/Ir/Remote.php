<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir;

use GibsonOS\Core\Attribute\ObjectMapper;
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
        #[IsAttribute(Key::class)] #[ObjectMapper(Key::class)] private array $keys = [],
        #[IsAttribute] private ?string $background = null
    ) {
    }

    public function setName(?string $name): Remote
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param Key[] $keys
     */
    public function setKeys(array $keys): Remote
    {
        $this->keys = $keys;

        return $this;
    }

    public function setBackground(?string $background): Remote
    {
        $this->background = $background;

        return $this;
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

    public function getModule(): ?Module
    {
        return null;
    }
}
