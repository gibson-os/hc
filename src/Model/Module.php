<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Model\AbstractModel;
use JsonSerializable;
use mysqlDatabase;

class Module extends AbstractModel implements JsonSerializable
{
    const MAX_ADDRESS = 119;

    private ?int $id;

    private ?int $deviceId;

    private string $name;

    private int $typeId;

    private ?string $config;

    private ?int $hertz;

    private ?int $bufferSize;

    private ?int $eepromSize;

    private ?int $pwmSpeed;

    private ?int $address;

    private ?int $ip;

    private ?int $masterId;

    private bool $offline = false;

    private ?DateTimeInterface $added;

    private ?DateTimeInterface $modified;

    private Type $type;

    private Master $master;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->type = new Type();
        $this->master = new Master();
    }

    public static function getTableName(): string
    {
        return 'hc_module';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Module
    {
        $this->id = $id;

        return $this;
    }

    public function getDeviceId(): ?int
    {
        return $this->deviceId;
    }

    public function setDeviceId(?int $deviceId): Module
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Module
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function setTypeId(int $typeId): Module
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function setConfig(?string $config): Module
    {
        $this->config = $config;

        return $this;
    }

    public function getHertz(): ?int
    {
        return $this->hertz;
    }

    public function setHertz(?int $hertz): Module
    {
        $this->hertz = $hertz;

        return $this;
    }

    public function getBufferSize(): ?int
    {
        return $this->bufferSize;
    }

    public function getDataBufferSize(): ?int
    {
        return $this->bufferSize - 2;
    }

    public function setBufferSize(?int $bufferSize): Module
    {
        $this->bufferSize = $bufferSize;

        return $this;
    }

    public function getEepromSize(): ?int
    {
        return $this->eepromSize;
    }

    public function setEepromSize(?int $eepromSize): Module
    {
        $this->eepromSize = $eepromSize;

        return $this;
    }

    public function getPwmSpeed(): ?int
    {
        return $this->pwmSpeed;
    }

    public function setPwmSpeed(?int $pwmSpeed): Module
    {
        $this->pwmSpeed = $pwmSpeed;

        return $this;
    }

    public function getAddress(): ?int
    {
        return $this->address;
    }

    public function setAddress(?int $address): Module
    {
        $this->address = $address;

        return $this;
    }

    public function getIp(): ?int
    {
        return $this->ip;
    }

    public function setIp(?int $ip): Module
    {
        $this->ip = $ip;

        return $this;
    }

    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    public function setMasterId(?int $masterId): Module
    {
        $this->masterId = $masterId;

        return $this;
    }

    public function isOffline(): bool
    {
        return $this->offline;
    }

    public function setOffline(bool $offline): Module
    {
        $this->offline = $offline;

        return $this;
    }

    public function getAdded(): ?DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(?DateTimeInterface $added): Module
    {
        $this->added = $added;

        return $this;
    }

    public function getModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(?DateTimeInterface $modified): Module
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @throws DateTimeError
     */
    public function getType(): Type
    {
        $this->loadForeignRecord($this->type, $this->getTypeId());

        return $this->type;
    }

    public function setType(Type $type): Module
    {
        $this->type = $type;
        $this->setTypeId((int) $type->getId());

        return $this;
    }

    /**
     * @throws DateTimeError
     */
    public function getMaster(): Master
    {
        $this->loadForeignRecord($this->master, $this->getMasterId());

        return $this->master;
    }

    public function setMaster(Master $master): Module
    {
        $this->master = $master;
        $this->setMasterId($master->getId());

        return $this;
    }

    /**
     * @throws DateTimeError
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'typeId' => $this->getTypeId(),
            'type' => $this->getType()->getName(),
            'hertz' => $this->getHertz(),
            'helper' => $this->getType()->getHelper(),
            'address' => $this->getAddress(),
            'offline' => $this->isOffline(),
            'settings' => $this->getType()->getUiSettings(),
        ];
    }
}
