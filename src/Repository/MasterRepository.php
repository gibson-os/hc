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

class MasterRepository extends AbstractRepository
{
    const START_ADDRESS = 2;

    /**
     * @throws DateTimeError
     * @throws GetError
     *
     * @return MasterModel[]
     */
    public function getByProtocol(string $protocol): array
    {
        $table = self::getTable(MasterModel::getTableName())
            ->setWhere('`protocol`=?')
            ->addWhereParameter($protocol)
        ;

        $models = [];

        if (!$table->selectPrepared()) {
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
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public function getById(int $id): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName())
            ->setWhere('`id`=?')
            ->addWhereParameter($id)
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Master unter der ID ' . $id . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new MasterModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     * @throws GetError
     */
    public function getByAddress(int $address, string $protocol): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName())
            ->setWhere('`protocol`=? AND `address`=?')
            ->setWhereParameters([$protocol, $address])
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Master unter der Adresse ' . $address . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new MasterModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public function getByName(string $name, string $protocol): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName())
            ->setWhere('`protocol`=? AND `name`=?')
            ->setWhereParameters([$protocol, $name])
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Master unter dem Name ' . $name . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new MasterModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    public function add(string $name, string $protocol): MasterModel
    {
        $table = self::getTable(MasterModel::getTableName());

        $table->setWhere('`protocol`=' . self::escape($protocol));
        $address = $table->selectAggregate('MAX(`address`)');

        if (empty($address)) {
            $address = self::START_ADDRESS;
        } else {
            $address = ((int) $address[0]) + 1;
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
     * @throws SelectError
     */
    public function getNextFreeAddress(int $masterId): int
    {
        $table = self::getTable(ModuleModel::getTableName());
        $table->setWhere('`master_id`=' . $masterId);

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
