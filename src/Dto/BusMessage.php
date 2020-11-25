<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto;

use GibsonOS\Core\Exception\GetError;

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
     * @var bool
     */
    private $isSend;

    /**
     * @var int|null
     */
    private $checksum;

    public function __construct(string $masterAddress, int $type, bool $isSend)
    {
        $this->masterAddress = $masterAddress;
        $this->type = $type;
        $this->isSend = $isSend;
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

    /**
     * @throws GetError
     */
    private function getSlaveAddressFromData(): int
    {
        if ($this->slaveAddress === null) {
            if (empty($this->data)) {
                throw new GetError('Slave address not transmitted!');
            }

            $this->slaveAddress = ord(substr($this->data, 0, 1));
            $this->data = substr($this->data, 1);
        }

        return $this->slaveAddress;
    }

    /**
     * @throws GetError
     */
    public function getSlaveAddress(): ?int
    {
        return $this->isSend ? $this->slaveAddress : $this->getSlaveAddressFromData();
    }

    public function setSlaveAddress(?int $slaveAddress): BusMessage
    {
        $this->slaveAddress = $slaveAddress;

        return $this;
    }

    /**
     * @throws GetError
     */
    private function getCommandFromData(): int
    {
        $this->getSlaveAddressFromData();

        if ($this->command === null) {
            if (empty($this->data)) {
                throw new GetError('Command not transmitted!');
            }

            $this->command = ord(substr($this->data, 0, 1));
            $this->data = substr($this->data, 1);
        }

        return $this->command;
    }

    /**
     * @throws GetError
     */
    public function getCommand(): ?int
    {
        return $this->isSend ? $this->command : $this->getCommandFromData();
    }

    public function setCommand(?int $command): BusMessage
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @throws GetError
     */
    public function getData(): ?string
    {
        if (!$this->isSend) {
            $this->getCommandFromData();
        }

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
}
