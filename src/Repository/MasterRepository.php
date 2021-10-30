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

    private const FIRST_SLAVE_ADDRESS = 8;

    /**
     * @throws SelectError
     * @throws DateTimeError
     * @return Master[]
     */
    public function getByProtocol(string $protocol): array
    {
        return $this->fetchAll('`protocol`=?', [$protocol], Master::class);
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(int $id): Master
    {
        $model = $this->fetchOne('`id`=?', [$id], Master::class);

        if (!$model instanceof Master) {
            throw new SelectError();
        }

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByAddress(string $address, string $protocol): Master
    {
        $model = $this->fetchOne(
            '`protocol`=? AND `address`=?',
            [$protocol, $address],
            Master::class
        );

        if (!$model instanceof Master) {
            throw new SelectError();
        }

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByName(string $name, string $protocol): Master
    {
        $model = $this->fetchOne(
            '`protocol`=? AND `name`=?',
            [$protocol, $name],
            Master::class
        );

        if (!$model instanceof Master) {
            throw new SelectError();
        }

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
        $address = self::FIRST_SLAVE_ADDRESS;

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

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Master[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll('`name` LIKE ?', [$name . '%'], Master::class);
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
