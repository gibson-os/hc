<?php
namespace GibsonOS\Module\Hc\Model;

use GibsonOS\Core\Model\AbstractModel;

/**
 * Class Type
 * @package GibsonOS\Module\Hc\Model
 */
class Type extends AbstractModel
{
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
    private $helper;
    /**
     * @var int
     */
    private $network;
    /**
     * @var int
     */
    private $hertz;
    /**
     * @var int
     */
    private $isHcSlave;
    /**
     * @var string|null
     */
    private $uiSettings;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_type';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Type
     */
    public function setId(int $id): Type
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
     * @return Type
     */
    public function setName(string $name): Type
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getHelper(): string
    {
        return $this->helper;
    }

    /**
     * @param string $helper
     * @return Type
     */
    public function setHelper(string $helper): Type
    {
        $this->helper = $helper;
        return $this;
    }

    /**
     * @return int
     */
    public function getNetwork(): int
    {
        return $this->network;
    }

    /**
     * @param int $network
     * @return Type
     */
    public function setNetwork(int $network): Type
    {
        $this->network = $network;
        return $this;
    }

    /**
     * @return int
     */
    public function getHertz(): int
    {
        return $this->hertz;
    }

    /**
     * @param int $hertz
     * @return Type
     */
    public function setHertz(int $hertz): Type
    {
        $this->hertz = $hertz;
        return $this;
    }

    /**
     * @return int
     */
    public function getisHcSlave(): int
    {
        return $this->isHcSlave;
    }

    /**
     * @param int $isHcSlave
     * @return Type
     */
    public function setIsHcSlave(int $isHcSlave): Type
    {
        $this->isHcSlave = $isHcSlave;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUiSettings(): ?string
    {
        return $this->uiSettings;
    }

    /**
     * @param string|null $uiSettings
     * @return Type
     */
    public function setUiSettings(?string $uiSettings): Type
    {
        $this->uiSettings = $uiSettings;
        return $this;
    }
}