<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Model\AbstractModel;
use JsonSerializable;
use mysqlDatabase;

class Log extends AbstractModel implements JsonSerializable
{
    public const DIRECTION_INPUT = 'input';

    public const DIRECTION_OUTPUT = 'output';

    private ?int $id = null;

    private ?int $moduleId = null;

    private ?DateTimeInterface $added = null;

    private ?int $masterId = null;

    private ?int $slaveAddress = null;

    private int $type;

    private ?int $command = null;

    /**
     * @deprecated
     */
    private string $data = '';

    private string $rawData = '';

    private string $direction;

    private Module $module;

    private Master $master;

    /** @var string|null Virtual Field */
    private ?string $text = null;

    /** @var string|null Virtual Field */
    private ?string $rendered = null;

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

    /**
     * @throws DateTimeError
     */
    public function getModule(): ?Module
    {
        if ($this->getModuleId() === null) {
            return null;
        }

        $this->loadForeignRecord($this->module, $this->getModuleId());

        return $this->module;
    }

    public function setModule(Module $module): Log
    {
        $this->module = $module;
        $this->setModuleId($module->getId());

        return $this;
    }

    /**
     * @throws DateTimeError
     */
    public function getMaster(): ?Master
    {
        if ($this->getMasterId() === null) {
            return null;
        }

        $this->loadForeignRecord($this->master, $this->getMasterId());

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
            'command' => $this->getCommand(),
            'data' => utf8_encode($this->getRawData()),
            'direction' => $this->getDirection(),
            'helper' => $module === null ? null : $module->getType()->getHelper(),
            'text' => $this->getText(),
            'rendered' => $this->getRendered(),
        ];
    }
}
