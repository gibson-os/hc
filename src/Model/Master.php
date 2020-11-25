<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Model\AbstractModel;

class Master extends AbstractModel
{
    const PROTOCOL_UDP = 'udp';

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $address;

    /**
     * @var DateTime
     */
    private $added;

    /**
     * @var DateTime
     */
    private $modified;

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

    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    public function setAdded(DateTime $added): Master
    {
        $this->added = $added;

        return $this;
    }

    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    public function setModified(DateTime $modified): Master
    {
        $this->modified = $modified;

        return $this;
    }
}
