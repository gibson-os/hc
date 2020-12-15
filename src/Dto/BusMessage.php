<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto;

class BusMessage
{
    /**
     * @var string
     */
    private $masterAddress;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int|null
     */
    private $slaveAddress;

    /**
     * @var int|null
     */
    private $command;

    /**
     * @var string|null
     */
    private $data;

    /**
     * @var bool
     */
    private $write = false;

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var int|null
     */
    private $checksum;

    public function __construct(string $masterAddress, int $type)
    {
        $this->masterAddress = $masterAddress;
        $this->type = $type;
    }

    public function getMasterAddress(): string
    {
        return $this->masterAddress;
    }

    public function setMasterAddress(string $masterAddress): BusMessage
    {
        $this->masterAddress = $masterAddress;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): BusMessage
    {
        $this->type = $type;

        return $this;
    }

    public function getSlaveAddress(): ?int
    {
        return $this->slaveAddress;
    }

    public function setSlaveAddress(?int $slaveAddress): BusMessage
    {
        $this->slaveAddress = $slaveAddress;

        return $this;
    }

    public function getCommand(): ?int
    {
        return $this->command;
    }

    public function setCommand(?int $command): BusMessage
    {
        $this->command = $command;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): BusMessage
    {
        $this->data = $data;

        return $this;
    }

    public function isWrite(): bool
    {
        return $this->write;
    }

    public function setWrite(bool $write): BusMessage
    {
        $this->write = $write;

        return $this;
    }

    public function getChecksum(): ?int
    {
        return $this->checksum;
    }

    public function setChecksum(?int $checksum): BusMessage
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): BusMessage
    {
        $this->port = $port;

        return $this;
    }
}
