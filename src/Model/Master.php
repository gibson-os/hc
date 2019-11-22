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
     * @var int
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

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_master';
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Master
     */
    public function setId(int $id): Master
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Master
     */
    public function setName(string $name): Master
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     *
     * @return Master
     */
    public function setProtocol(string $protocol): Master
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * @return int
     */
    public function getAddress(): int
    {
        return $this->address;
    }

    /**
     * @param int $address
     *
     * @return Master
     */
    public function setAddress(int $address): Master
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    /**
     * @param DateTime $added
     *
     * @return Master
     */
    public function setAdded(DateTime $added): Master
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTime $modified
     *
     * @return Master
     */
    public function setModified(DateTime $modified): Master
    {
        $this->modified = $modified;

        return $this;
    }
}
