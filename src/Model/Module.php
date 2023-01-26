<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Core\Utility\JsonUtility;

/**
 * @method Type        getType()
 * @method Module      setType(Type $type)
 * @method Master|null getMaster()
 * @method Module      setMaster(?Master $master)
 */
#[Table]
#[Key(unique: true, columns: ['master_id', 'address'])]
class Module extends AbstractModel implements \JsonSerializable, AutoCompleteModelInterface
{
    public const MAX_ADDRESS = 119;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $deviceId = null;

    #[Column(length: 64)]
    private string $name;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $typeId;

    #[Column(type: Column::TYPE_TEXT)]
    private ?string $config = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $hertz = null;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $bufferSize = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $eepromSize = null;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $pwmSpeed = null;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $address = null;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    #[Key(true)]
    private ?int $ip = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $masterId = null;

    #[Column]
    private bool $offline = false;

    #[Column(default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private \DateTimeInterface $added;

    #[Column(type: Column::TYPE_TIMESTAMP, attributes: [Column::ATTRIBUTE_CURRENT_TIMESTAMP])]
    private \DateTimeInterface $modified;

    #[Constraint]
    protected Type $type;

    #[Constraint]
    protected ?Master $master;

    /**
     * @deprecated
     */
    #[Column]
    private ?int $via = null;

    /**
     * @deprecated
     */
    #[Column]
    private ?int $group = null;

    public function __construct(\mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->added = new \DateTimeImmutable();
        $this->modified = new \DateTimeImmutable();
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

    public function getAdded(): \DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(\DateTimeInterface $added): Module
    {
        $this->added = $added;

        return $this;
    }

    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(\DateTimeInterface $modified): Module
    {
        $this->modified = $modified;

        return $this;
    }

    public function getVia(): ?int
    {
        return $this->via;
    }

    public function setVia(?int $via): Module
    {
        $this->via = $via;

        return $this;
    }

    public function getGroup(): ?int
    {
        return $this->group;
    }

    public function setGroup(?int $group): Module
    {
        $this->group = $group;

        return $this;
    }

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
            'settings' => JsonUtility::decode($this->getType()->getUiSettings() ?? '[]'),
            'added' => $this->added->format('Y-m-d H:i:s'),
            'modified' => $this->modified->format('Y-m-d H:i:s'),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
