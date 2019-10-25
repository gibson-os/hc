<?php
namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use mysqlDatabase;

class Module extends AbstractModel
{
    const MAX_ADDRESS = 119;

    /**
     * @var int|null
     */
    private $id;
    /**
     * @var int|null
     */
    private $deviceId;
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $typeId;
    /**
     * @var string|null
     */
    private $config;
    /**
     * @var int|null
     */
    private $hertz;
    /**
     * @var int|null
     */
    private $bufferSize;
    /**
     * @var int|null
     */
    private $eepromSize;
    /**
     * @var int|null
     */
    private $pwmSpeed;
    /**
     * @var int|null
     */
    private $address;
    /**
     * @var int|null
     */
    private $ip;
    /**
     * @var int|null
     */
    private $masterId;
    /**
     * @var bool
     */
    private $offline;
    /**
     * @var DateTime|null
     */
    private $added;
    /**
     * @var DateTime|null
     */
    private $modified;

    /**
     * @var Type
     */
    private $type;
    /**
     * @var Master
     */
    private $master;

    /**
     * @param mysqlDatabase|null $database
     */
    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->type = new Type();
        $this->master = new Master();
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_module';
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
     * @return Module
     */
    public function setId(int $id): Module
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDeviceId(): ?int
    {
        return $this->deviceId;
    }

    /**
     * @param int|null $deviceId
     * @return Module
     */
    public function setDeviceId(?int $deviceId): Module
    {
        $this->deviceId = $deviceId;
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
     * @return Module
     */
    public function setName(string $name): Module
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getTypeId(): int
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     * @return Module
     */
    public function setTypeId(int $typeId): Module
    {
        $this->typeId = $typeId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfig(): ?string
    {
        return $this->config;
    }

    /**
     * @param string|null $config
     * @return Module
     */
    public function setConfig(?string $config): Module
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getHertz(): ?int
    {
        return $this->hertz;
    }

    /**
     * @param int|null $hertz
     * @return Module
     */
    public function setHertz(?int $hertz): Module
    {
        $this->hertz = $hertz;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getBufferSize(): ?int
    {
        return $this->bufferSize;
    }

    /**
     * @return int|null
     */
    public function getDataBufferSize(): ?int
    {
        return $this->bufferSize - 2;
    }

    /**
     * @param int|null $bufferSize
     * @return Module
     */
    public function setBufferSize(?int $bufferSize): Module
    {
        $this->bufferSize = $bufferSize;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEepromSize(): ?int
    {
        return $this->eepromSize;
    }

    /**
     * @param int|null $eepromSize
     * @return Module
     */
    public function setEepromSize(?int $eepromSize): Module
    {
        $this->eepromSize = $eepromSize;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPwmSpeed(): ?int
    {
        return $this->pwmSpeed;
    }

    /**
     * @param int|null $pwmSpeed
     * @return Module
     */
    public function setPwmSpeed(?int $pwmSpeed): Module
    {
        $this->pwmSpeed = $pwmSpeed;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAddress(): ?int
    {
        return $this->address;
    }

    /**
     * @param int|null $address
     * @return Module
     */
    public function setAddress(?int $address): Module
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getIp(): ?int
    {
        return $this->ip;
    }

    /**
     * @param int|null $ip
     * @return Module
     */
    public function setIp(?int $ip): Module
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    /**
     * @param int|null $masterId
     * @return Module
     */
    public function setMasterId(?int $masterId): Module
    {
        $this->masterId = $masterId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOffline(): bool
    {
        return $this->offline;
    }

    /**
     * @param bool $offline
     * @return Module
     */
    public function setOffline(bool $offline): Module
    {
        $this->offline = $offline;
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
     * @param DateTime|null $added
     * @return Module
     */
    public function setAdded(?DateTime $added): Module
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
     * @param DateTime|null $modified
     * @return Module
     */
    public function setModified(?DateTime $modified): Module
    {
        $this->modified = $modified;
        return $this;
    }

    /**
     * @return Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @param Type $type
     * @return Module
     */
    public function setType(Type $type): Module
    {
        $this->type = $type;
        $this->setTypeId($type->getId());

        return $this;
    }

    /**
     * @return Master
     */
    public function getMaster(): Master
    {
        return $this->master;
    }

    /**
     * @param Master $master
     * @return Module
     */
    public function setMaster(Master $master): Module
    {
        $this->master = $master;
        $this->setMasterId($master->getId());

        return $this;
    }

    /**
     * @throws SelectError
     * @return Module
     */
    public function loadType()
    {
        $this->loadForeignRecord($this->getType(), $this->getTypeId());
        return $this;
    }

    /**
     * @throws SelectError
     * @return Module
     */
    public function loadMaster()
    {
        $this->loadForeignRecord($this->getMaster(), $this->getMasterId());
        return $this;
    }
}