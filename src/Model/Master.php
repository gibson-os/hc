<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface as AutoCompleteModelInterfaceAlias;

class Master extends AbstractModel implements AutoCompleteModelInterfaceAlias
{
    const PROTOCOL_UDP = 'udp';

    private ?int $id = null;

    private string $name;

    private string $protocol;

    private string $address;

    private int $sendPort;

    private ?DateTimeInterface $added = null;

    private ?DateTimeInterface $modified = null;

    public static function getTableName(): string
    {
        return 'hc_master';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Master
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Master
    {
        $this->name = $name;

        return $this;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function setProtocol(string $protocol): Master
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): Master
    {
        $this->address = $address;

        return $this;
    }

    public function getSendPort(): int
    {
        return $this->sendPort;
    }

    public function setSendPort(int $sendPort): Master
    {
        $this->sendPort = $sendPort;

        return $this;
    }

    public function getAdded(): ?DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(DateTimeInterface $added): Master
    {
        $this->added = $added;

        return $this;
    }

    public function getModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(DateTimeInterface $modified): Master
    {
        $this->modified = $modified;

        return $this;
    }

    public function getAutoCompleteId(): int
    {
        return (int) $this->getId();
    }
}
