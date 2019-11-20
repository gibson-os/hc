<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

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
     * @param string   $name
     * @param int|null $typeId
     *
     * @throws SelectError
     *
     * @return ModuleModel[]
     */
    public static function findByName(string $name, int $typeId = null)
    {
        $tableName = ModuleModel::getTableName();
        $table = self::getTable($tableName);

        $where = '`name` LIKE \'' . self::escape($name, false) . '%\'';

        if ($typeId !== null) {
            $where .= ' AND `type_id`=' . self::escape($typeId);
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
     * @param int $masterId
     *
     * @return ModuleModel[]
     */
    public static function getByMasterId($masterId)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`master_id`=' . self::escape($masterId));

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
     * @param int $deviceId
     *
     * @throws SelectError
     *
     * @return ModuleModel
     */
    public static function getByDeviceId($deviceId)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`device_id`=' . self::escape($deviceId));
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
     * @param int $id
     *
     * @throws SelectError
     *
     * @return ModuleModel
     */
    public static function getById($id)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`id`=' . self::escape($id));
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
     * @param int $address
     * @param int $masterId
     *
     * @throws SelectError
     *
     * @return ModuleModel
     */
    public static function getByAddress($address, $masterId)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere(
            '`address`=' . self::escape($address) . ' AND ' .
            '`master_id`=' . self::escape($masterId)
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
     * @param int $tryCount
     *
     * @throws GetError
     *
     * @return int
     */
    public static function getFreeDeviceId($tryCount = 0)
    {
        $deviceId = mt_rand(1, AbstractHcSlave::MAX_DEVICE_ID);
        ++$tryCount;

        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`device_id`=' . self::escape($deviceId));
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
     * @param $id
     *
     * @throws DeleteError
     */
    public static function deleteById($id)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`id`=' . self::escape($id));

        if (!$table->delete()) {
            $exception = new DeleteError('Modul konnten nicht gelöscht werden!');
            $exception->setTable($table);

            throw $exception;
        }
    }
}
