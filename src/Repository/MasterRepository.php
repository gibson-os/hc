<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type\DefaultAddress;

class MasterRepository extends AbstractRepository
{
    private const MIN_PORT = 42001;

    private const MAX_PORT = 42999;

    /**
     * @throws DateTimeError
     *
     * @return Master[]
     */
    public function getByProtocol(string $protocol): array
    {
        $table = self::getTable(Master::getTableName())
            ->setWhere('`protocol`=?')
            ->addWhereParameter($protocol)
        ;

        $models = [];

        if (!$table->selectPrepared()) {
            return $models;
        }

        do {
            $model = new Master();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(int $id): Master
    {
        $table = self::getTable(Master::getTableName())
            ->setWhere('`id`=?')
            ->addWhereParameter($id)
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Master unter der ID ' . $id . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Master();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByAddress(string $address, string $protocol): Master
    {
        $table = self::getTable(Master::getTableName())
            ->setWhere('`protocol`=? AND `address`=?')
            ->setWhereParameters([$protocol, $address])
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Master unter der Adresse ' . $address . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Master();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByName(string $name, string $protocol): Master
    {
        $table = self::getTable(Master::getTableName())
            ->setWhere('`protocol`=? AND `name`=?')
            ->setWhereParameters([$protocol, $name])
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Master unter dem Name ' . $name . ' existiert nicht!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Master();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    public function add(string $name, string $protocol, string $address): Master
    {
        $model = (new Master())
            ->setName($name)
            ->setProtocol($protocol)
            ->setAddress($address)
            ->setSendPort($this->findFreePort())
            ->setAdded(new DateTime());
        $model->save();

        return $model;
    }

    /**
     * @throws SelectError
     */
    public function getNextFreeAddress(int $masterId): int
    {
        $table = $this->getTable(Module::getTableName());
        $table->setWhere('`master_id`=' . $masterId);

        $typeDefaultAddressTable = $this->getTable(DefaultAddress::getTableName());
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

            if ($address > Module::MAX_ADDRESS) {
                $exception = new SelectError('Keine freie Adresse vorhanden!');
                $exception->setTable($table);

                throw $exception;
            }
        }

        return $address;
    }

    private function findFreePort(): int
    {
        $table = $this->getTable(Master::getTableName());
        $port = mt_rand(self::MIN_PORT, self::MAX_PORT);
        $table
            ->setWhere('`send_port`=?')
            ->addWhereParameter($port)
        ;

        if ($table->select(false)) {
            return $this->findFreePort();
        }

        return $port;
    }
}
