<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Master as MasterModel;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use GibsonOS\Module\Hc\Model\Type\DefaultAddress;

class Master extends AbstractRepository
{
    const START_ADDRESS = 2;

    /**
     * @param string $protocol
     *
     * @return MasterModel[]
     */
    public static function getByProtocol($protocol)
    {
        $table = self::getTable(MasterModel::getTableName());
        $table->setWhere('`protocol`=' . self::escape($protocol));

        $models = [];

        if (!$table->select()) {
            return $models;
        }

        do {
            $model = new MasterModel();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }

    /**
     * @param int $id
     *
     * @throws SelectError
     *
     * @return MasterModel
     */
    public static function getById($id)
    {
        $table = self::getTable(MasterModel::getTableName());
        $table->setWhere('`id`=' . self::escape($id));
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Master unter der ID ' . $id . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new MasterModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @param int    $address
     * @param string $protocol
     *
     * @throws SelectError
     * @throws DateTimeError
     * @throws GetError
     *
     * @return MasterModel
     */
    public static function getByAddress(int $address, string $protocol): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName());
        $table->setWhere(
            '`protocol`=' . self::escape($protocol) . ' AND ' .
            '`address`=' . self::escape((string) $address)
        );
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Master unter der Adresse ' . $address . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new MasterModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @param string $name
     * @param string $protocol
     *
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     *
     * @return MasterModel
     */
    public static function getByName(string $name, string $protocol): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName());
        $table->setWhere(
            '`protocol`=' . self::escape($protocol) . ' AND ' .
            '`name`=' . self::escape($name)
        );
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Master unter dem Name ' . $name . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new MasterModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @param string $name
     * @param string $protocol
     *
     * @throws SaveError
     * @throws Exception
     *
     * @return MasterModel
     */
    public static function add(string $name, string $protocol): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName());

        $table->setWhere('`protocol`=' . self::escape($protocol));
        $address = $table->selectAggregate('MAX(`address`)');

        if (
            !$address ||
            $address[0] === null
        ) {
            $address = self::START_ADDRESS;
        } else {
            $address = $address[0] + 1;
        }

        $model = new MasterModel();
        $model->setName($name);
        $model->setAddress($address);
        $model->setProtocol($protocol);
        $model->setAdded(new DateTime());
        $model->save();

        return $model;
    }

    /**
     * @param int $masterId
     *
     * @throws SelectError
     *
     * @return int
     */
    public static function getNextFreeAddress($masterId)
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`master_id`=' . self::escape($masterId));

        $typeDefaultAddressTable = self::getTable(DefaultAddress::getTableName());
        $typeDefaultAddressTable->setSelectString('`address`');

        $table->appendUnion(null, '`address`');
        $table->appendUnion($typeDefaultAddressTable->getSelect());
        $table->setOrderBy('`address`');

        if (!$table->selectUnion(false)) {
            $exception = new SelectError('Konnte reservierte Adressen nicht laden!');
            $exception->setTable($table);

            throw $exception;
        }

        $reservedAddresses = $table->connection->fetchResultList();
        $address = 3;

        while (in_array($address, $reservedAddresses)) {
            ++$address;

            if ($address > ModuleModel::MAX_ADDRESS) {
                $exception = new SelectError('Keine freie Adresse vorhanden!');
                $exception->setTable($table);

                throw $exception;
            }
        }

        return $address;
    }
}
