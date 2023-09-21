<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use Exception;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type\DefaultAddress;

class MasterRepository extends AbstractRepository
{
    private const MIN_PORT = 42001;

    private const MAX_PORT = 42999;

    private const FIRST_SLAVE_ADDRESS = 8;

    public function __construct(
        private ModelManager $modelManager,
        #[GetTableName(Master::class)]
        private string $masterTableName,
        #[GetTableName(DefaultAddress::class)]
        private string $defaultAddressTableName,
        #[GetTableName(Module::class)]
        private string $moduleTableName,
    ) {
    }

    /**
     * @throws SelectError
     *
     * @return Master[]
     */
    public function getByProtocol(string $protocol): array
    {
        return $this->fetchAll('`protocol`=?', [$protocol], Master::class);
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Master
    {
        return $this->fetchOne('`id`=?', [$id], Master::class);
    }

    /**
     * @throws SelectError
     */
    public function getByAddress(string $address, string $protocol): Master
    {
        return $this->fetchOne('`protocol`=? AND `address`=?', [$protocol, $address], Master::class);
    }

    /**
     * @throws SelectError
     */
    public function getByName(string $name, string $protocol): Master
    {
        return $this->fetchOne('`protocol`=? AND `name`=?', [$protocol, $name], Master::class);
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
        $this->modelManager->save($model);

        return $model;
    }

    /**
     * @throws SelectError
     */
    public function getNextFreeAddress(int $masterId): int
    {
        $table = $this->getTable($this->moduleTableName)
            ->setWhere('`master_id`=?')
            ->addWhereParameter($masterId)
        ;
        $table->setSelectString('`address`');

        $typeDefaultAddressTable = $this->getTable($this->defaultAddressTableName);
        $typeDefaultAddressTable->setSelectString('`address`');

        $table->appendUnion($typeDefaultAddressTable->getSelect());
        $table->setOrderBy('`address`');

        if ($table->selectPrepared(false) === false) {
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
     *
     * @return Master[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll('`name` LIKE ?', [$name . '%'], Master::class);
    }

    private function findFreePort(): int
    {
        $table = $this->getTable($this->masterTableName);
        $port = mt_rand(self::MIN_PORT, self::MAX_PORT);
        $table
            ->setWhere('`send_port`=?')
            ->addWhereParameter($port)
        ;

        if (!$table->selectPrepared(false)) {
            return $this->findFreePort();
        }

        return $port;
    }
}
