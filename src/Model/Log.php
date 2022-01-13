<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use JsonSerializable;
use mysqlDatabase;

#[Table]
class Log extends AbstractModel implements JsonSerializable
{
    public const DIRECTION_INPUT = 'input';

    public const DIRECTION_OUTPUT = 'output';

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $moduleId = null;

    #[Column(default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private ?DateTimeInterface $added;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $masterId = null;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $slaveAddress = null;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $type;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $command = null;

    /**
     * @deprecated
     */
    #[Column(length: 192)]
    private string $data;

    #[Column(type: Column::TYPE_VARBINARY, length: 128)]
    private string $rawData = '';

    #[Column(type: Column::TYPE_ENUM, values: ['input', 'output'])]
    private string $direction;

    private Module $module;

    private Master $master;

    /** @var string|null Virtual Field */
    private ?string $text = null;

    /** @var string|null Virtual Field */
    private ?string $rendered = null;

    /** @var string|null Virtual Field */
    private ?string $commandText = null;

    /** @var Explain[]|null Virtual Field */
    private ?array $explains = null;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->module = new Module();
        $this->master = new Master();
    }

    public static function getTableName(): string
    {
        return 'hc_log';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Log
    {
        $this->id = $id;

        return $this;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function setModuleId(?int $moduleId): Log
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getAdded(): ?DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(DateTimeInterface $added): Log
    {
        $this->added = $added;

        return $this;
    }

    public function getMasterId(): ?int
    {
        return $this->masterId;
    }

    public function setMasterId(?int $masterId): Log
    {
        $this->masterId = $masterId;

        return $this;
    }

    public function getSlaveAddress(): ?int
    {
        return $this->slaveAddress;
    }

    public function setSlaveAddress(?int $slaveAddress): Log
    {
        $this->slaveAddress = $slaveAddress;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): Log
    {
        $this->type = $type;

        return $this;
    }

    public function getCommand(): ?int
    {
        return $this->command;
    }

    public function setCommand(?int $command): Log
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @deprecated use getRawData
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @deprecated use sezRawData
     */
    public function setData(string $data): Log
    {
        $this->data = $data;

        return $this;
    }

    public function getRawData(): string
    {
        return $this->rawData;
    }

    public function setRawData(string $rawData): Log
    {
        $this->rawData = $rawData;

        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): Log
    {
        $this->direction = $direction;

        return $this;
    }

    public function getModule(): ?Module
    {
        $moduleId = $this->getModuleId();

        if ($moduleId === null) {
            return null;
        }

        $this->loadForeignRecord($this->module, $moduleId);

        return $this->module;
    }

    public function setModule(Module $module): Log
    {
        $this->module = $module;
        $this->setModuleId($module->getId());

        return $this;
    }

    public function getMaster(): ?Master
    {
        $masterId = $this->getMasterId();

        if ($masterId === null) {
            return null;
        }

        $this->loadForeignRecord($this->master, $masterId);

        return $this->master;
    }

    public function setMaster(Master $master): Log
    {
        $this->master = $master;
        $this->setMasterId($master->getId());

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): Log
    {
        $this->text = $text;

        return $this;
    }

    public function getRendered(): ?string
    {
        return $this->rendered;
    }

    public function setRendered(?string $rendered): Log
    {
        $this->rendered = $rendered;

        return $this;
    }

    public function getCommandText(): ?string
    {
        return $this->commandText;
    }

    public function setCommandText(?string $commandText): Log
    {
        $this->commandText = $commandText;

        return $this;
    }

    /**
     * @return Explain[]|null
     */
    public function getExplains(): ?array
    {
        return $this->explains;
    }

    /**
     * @param Explain[]|null $explains
     */
    public function setExplains(?array $explains): void
    {
        $this->explains = $explains;
    }

    public function jsonSerialize(): array
    {
        $module = $this->getModule();
        $master = $this->getMaster();
        $added = $this->getAdded();

        return [
            'id' => $this->getId(),
            'moduleId' => $this->getModuleId(),
            'moduleName' => $module === null ? null : $module->getName(),
            'masterId' => $this->getMasterId(),
            'masterName' => $master === null ? null : $master->getName(),
            'added' => $added === null ? null : $added->format('Y-m-d H:i:s'),
            'slaveAddress' => $this->getSlaveAddress(),
            'type' => $this->getType(),
            'command' => $this->getCommandText() ?? $this->getCommand(),
            'data' => utf8_encode($this->getRawData()),
            'direction' => $this->getDirection(),
            'helper' => $module === null ? null : $module->getType()->getHelper(),
            'text' => $this->getText(),
            'rendered' => $this->getRendered(),
            'explains' => $this->getExplains(),
        ];
    }
}
