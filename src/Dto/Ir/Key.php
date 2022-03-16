<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ir;

use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Model\Module;
use JsonSerializable;

class Key implements JsonSerializable, AutoCompleteModelInterface, AttributeInterface
{
    public function __construct(
        private int $protocol,
        private int $address,
        private int $command,
        #[IsAttribute] private ?string $name = null,
        private ?string $protocolName = null
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

    public function getProtocolName(): ?string
    {
        return $this->protocolName;
    }

    public function setProtocolName(?string $protocolName): Key
    {
        $this->protocolName = $protocolName;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getSubId(),
            'protocol' => $this->getProtocol(),
            'address' => $this->getAddress(),
            'command' => $this->getCommand(),
            'name' => $this->getName(),
            'protocolName' => $this->getProtocolName(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getSubId();
    }

    public function getSubId(): int
    {
        return $this->getProtocol() << 32 | $this->getAddress() << 16 | $this->getCommand();
    }

    public function getModule(): ?Module
    {
        return null;
    }
}
