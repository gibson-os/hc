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

    public function __construct(private LoggerInterface $logger)
    {
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

        $where = '`name` LIKE ?';
        $parameters = [$name . '%'];

        if ($typeId !== null) {
            $where .= ' AND `type_id`=' . $typeId;
            $parameters[] = $typeId;
        }

        $table->setWhere($where);

        return $this->fetchAll($where, $parameters, Module::class);
    }

    /**
     * @throws DateTimeError
     *
     * @return Module[]
     */
    public function getByMasterId(int $masterId): array
    {
        $this->logger->debug(sprintf('Get slaves by master id %d', $masterId));

        return $this->fetchAll('`master_id`=?', [$masterId], Module::class);
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByDeviceId(int $deviceId): Module
    {
        $this->logger->debug(sprintf('Get slave by device ID %d', $deviceId));

        $model = $this->fetchOne('`device_id`=?', [$deviceId], Module::class);

        if (!$model instanceof Module) {
            throw new SelectError('Kein Modul unter der Device ID ' . $deviceId . ' bekannt!');
        }

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(int $id): Module
    {
        $this->logger->debug(sprintf('Get slave by id %s', $id));

        $model = $this->fetchOne('`id`=?', [$id], Module::class);

        if (!$model instanceof Module) {
            throw new SelectError('Kein Modul unter der ID ' . $id . ' bekannt!');
        }

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByAddress(int $address, int $masterId): Module
    {
        $this->logger->debug(sprintf('Get slave by address %d and master id %d', $address, $masterId));

        $model = $this->fetchOne(
            '`address`=? AND `master_id`=?',
            [$address, $masterId],
            Module::class
        );

        if (!$model instanceof Module) {
            throw new SelectError('Kein Modul unter der Adresse ' . $address . ' bekannt!');
        }

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
     * @param int[] $ids
     *
     * @throws DeleteError
     */
    public function deleteByIds(array $ids)
    {
        $this->logger->debug(sprintf('Delete slaves by IDs %s', implode(', ', $ids)));

        $table = self::getTable(Module::getTableName());
        $table
            ->setWhere('`id` IN (' . $table->getParametersString($ids) . ')')
            ->setWhereParameters($ids)
        ;

        if (!$table->deletePrepared()) {
            $exception = new DeleteError(sprintf('Slaves %s could not be deleted', implode(', ', $ids)));
            $exception->setTable($table);

            throw $exception;
        }
    }
}
