<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir;

use JsonSerializable;

class Key implements JsonSerializable
{
    public function __construct(
        private int $protocol,
        private int $address,
        private int $command,
        private ?string $name = null
    ) {
    }

    public function getProtocol(): int
    {
        return $this->protocol;
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function getCommand(): int
    {
        return $this->command;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Key
    {
        $this->name = $name;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'protocol' => $this->getProtocol(),
            'address' => $this->getAddress(),
            'command' => $this->getCommand(),
            'name' => $this->getName(),
        ];
    }
}
