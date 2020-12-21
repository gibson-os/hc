<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Model\AbstractModel;
use mysqlDatabase;

class Log extends AbstractModel
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

    private string $data;

    private string $direction;

    private Module $module;

    private Master $master;

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

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): Log
    {
        $this->data = $data;

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
    public function getModule(): Module
    {
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
    public function getMaster(): Master
    {
        $this->loadForeignRecord($this->master, $this->getMasterId());

        return $this->master;
    }

    public function setMaster(Master $master): Log
    {
        $this->master = $master;
        $this->setMasterId($master->getId());

        return $this;
    }
}
