<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

class Module extends AbstractRepository
{
    const START_ADDRESS = 2;

    const MAX_GENERATE_DEVICE_ID_RETRY = 10;

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     *
     * @return ModuleModel[]
     */
    public static function findByName(string $name, int $typeId = null): array
    {
        $tableName = ModuleModel::getTableName();
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
            $model = new ModuleModel();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     *
     * @return ModuleModel[]
     */
    public static function getByMasterId(int $masterId): array
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`master_id`=' . $masterId);

        $models = [];

        if (!$table->select()) {
            return $models;
        }

        do {
            $model = new ModuleModel();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public static function getByDeviceId(int $deviceId): ModuleModel
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`device_id`=' . $deviceId);
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Modul unter der Device ID ' . $deviceId . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new ModuleModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public static function getById(int $id): ModuleModel
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`id`=' . $id);
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Modul unter der ID ' . $id . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new ModuleModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public static function getByAddress(int $address, int $masterId): ModuleModel
    {
        $table = self::getTable(ModuleModel::getTableName());
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

        $model = new ModuleModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws GetError
     */
    public static function getFreeDeviceId(int $tryCount = 0): int
    {
        $deviceId = mt_rand(1, AbstractHcSlave::MAX_DEVICE_ID);
        ++$tryCount;

        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`device_id`=' . $deviceId);
        $table->setLimit(1);

        $count = $table->selectAggregate('COUNT(`device_id`)');

        if ($count[0]) {
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
    public static function deleteById(int $id)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`id`=' . $id);

        if (!$table->delete()) {
            $exception = new DeleteError('Modul konnten nicht gelöscht werden!');
            $exception->setTable($table);

            throw $exception;
        }
    }
}
