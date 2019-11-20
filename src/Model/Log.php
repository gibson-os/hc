<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTime;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use mysqlDatabase;

class Log extends AbstractModel
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int|null
     */
    private $moduleId;

    /**
     * @var DateTime|null
     */
    private $added;

    /**
     * @var int|null
     */
    private $masterId;

    /**
     * @var int|null
     */
    private $slaveAddress;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int|null
     */
    private $command;

    /**
     * @var string
     */
    private $data;

    /**
     * @var string
     */
    private $direction;

    /**
     * @var Module
     */
    private $module;

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

        $this->module = new Module();
        $this->master = new Master();
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_log';
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
     *
     * @return Log
     */
    public function setId(int $id): Log
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    /**
     * @param int|null $moduleId
     *
     * @return Log
     */
    public function setModuleId(?int $moduleId): Log
    {
        $this->moduleId = $moduleId;

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
     * @return Log
     */
    public function setAdded(DateTime $added): Log
    {
        $this->added = $added;

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
     *
     * @return Log
     */
    public function setMasterId(?int $masterId): Log
    {
        $this->masterId = $masterId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSlaveAddress(): ?int
    {
        return $this->slaveAddress;
    }

    /**
     * @param int|null $slaveAddress
     *
     * @return Log
     */
    public function setSlaveAddress(?int $slaveAddress): Log
    {
        $this->slaveAddress = $slaveAddress;

        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return Log
     */
    public function setType(int $type): Log
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCommand(): ?int
    {
        return $this->command;
    }

    /**
     * @param int|null $command
     *
     * @return Log
     */
    public function setCommand(?int $command): Log
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return Log
     */
    public function setData(string $data): Log
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     *
     * @return Log
     */
    public function setDirection(string $direction): Log
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * @return Module
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * @param Module $module
     *
     * @return Log
     */
    public function setModule(Module $module): Log
    {
        $this->module = $module;
        $this->setModuleId($module->getId());

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
     *
     * @return Log
     */
    public function setMaster(Master $master): Log
    {
        $this->master = $master;
        $this->setMasterId($master->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Log
     */
    public function loadMaster()
    {
        $this->loadForeignRecord($this->getMaster(), $this->getMasterId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Log
     */
    public function loadModule()
    {
        $this->loadForeignRecord($this->getModule(), $this->getModuleId());

        return $this;
    }
}
