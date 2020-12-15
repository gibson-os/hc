<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use Psr\Log\LoggerInterface;

class ModuleRepository extends AbstractRepository
{
    private const MAX_GENERATE_DEVICE_ID_RETRY = 10;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(string $name, Type $type): Module
    {
        $this->logger->debug(sprintf('Create slave with name %d and type %d', $name, $type->getName()));

        return (new Module())
            ->setName($name)
            ->setType($type)
        ;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Module[]
     */
    public function findByName(string $name, int $typeId = null): array
    {
        $this->logger->debug(sprintf('Find slave with name %d and type id %s', $name, $typeId ?? 0));

        $tableName = Module::getTableName();
        $table = self::getTable($tableName);

        $where = '`name` LIKE \'' . self::escapeWithoutQuotes($name) . '%\'';

        if ($typeId !== null) {
            $where .= ' AND `type_id`=' . $typeId;
        }

        $table->setWhere($where);

        if (!$table->select()) {
            $exception = new SelectError('Keine Module mit dem Namen ' . $name . '* vorhanden!');
            $exception->setTable($table);

            throw $exception;
        }

        $models = [];

        do {
            $model = new Module();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }

    /**
     * @throws DateTimeError
     *
     * @return Module[]
     */
    public function getByMasterId(int $masterId): array
    {
        $this->logger->debug(sprintf('Get slaves by master id %d', $masterId));

        $table = self::getTable(Module::getTableName());
        $table->setWhere('`master_id`=' . $masterId);

        $models = [];

        if (!$table->select()) {
            return $models;
        }

        do {
            $model = new Module();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByDeviceId(int $deviceId): Module
    {
        $this->logger->debug(sprintf('Get slave by device ID %d', $deviceId));

        $table = self::getTable(Module::getTableName());
        $table->setWhere('`device_id`=' . $deviceId);
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Modul unter der Device ID ' . $deviceId . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Module();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(int $id): Module
    {
        $this->logger->debug(sprintf('Get slave by id %s', $id));

        $table = self::getTable(Module::getTableName());
        $table->setWhere('`id`=' . $id);
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Modul unter der ID ' . $id . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Module();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByAddress(int $address, int $masterId): Module
    {
        $this->logger->debug(sprintf('Get slave by address %d and master id %d', $address, $masterId));

        $table = self::getTable(Module::getTableName());
        $table->setWhere(
            '`address`=' . $address . ' AND ' .
            '`master_id`=' . $masterId
        );
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Modul unter der Adresse ' . $address . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Module();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws GetError
     */
    public function getFreeDeviceId(int $tryCount = 0): int
    {
        $this->logger->debug(sprintf('Get free device id. Try %d', $tryCount));

        $deviceId = mt_rand(1, AbstractHcSlave::MAX_DEVICE_ID);
        ++$tryCount;

        $table = self::getTable(Module::getTableName());
        $table->setWhere('`device_id`=' . $deviceId);
        $table->setLimit(1);

        $count = $table->selectAggregate('COUNT(`device_id`)');

        if (!empty($count) && (int) $count[0] > 0) {
            if ($tryCount === self::MAX_GENERATE_DEVICE_ID_RETRY) {
                throw new GetError('Es konnte keine freie Device ID ermittelt werden!');
            }

            return self::getFreeDeviceId($tryCount);
        }

        return $deviceId;
    }

    /**
     * @throws DeleteError
     */
    public function deleteById(int $id)
    {
        $this->logger->debug(sprintf('Delete slave by id %s', $id));

        $table = self::getTable(Module::getTableName());
        $table->setWhere('`id`=' . $id);

        if (!$table->delete()) {
            $exception = new DeleteError('Modul konnten nicht gelöscht werden!');
            $exception->setTable($table);

            throw $exception;
        }
    }
}
