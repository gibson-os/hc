<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
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

    public function __construct(
        private LoggerInterface $logger,
        #[GetTableName(Module::class)] private string $moduleTableName
    ) {
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
     *
     * @return Module[]
     */
    public function findByName(string $name, int $typeId = null): array
    {
        $this->logger->debug(sprintf('Find slave with name %s and type id %d', $name, $typeId ?? 0));

        $tableName = $this->moduleTableName;
        $table = self::getTable($tableName);

        $where = '`name` LIKE ?';
        $parameters = [$name . '%'];

        if ($typeId !== null) {
            $where .= ' AND `type_id`=?';
            $parameters[] = $typeId;
        }

        $table->setWhere($where);

        return $this->fetchAll($where, $parameters, Module::class);
    }

    /**
     * @throws SelectError
     *
     * @return Module[]
     */
    public function getByMasterId(int $masterId): array
    {
        $this->logger->debug(sprintf('Get slaves by master id %d', $masterId));

        return $this->fetchAll('`master_id`=?', [$masterId], Module::class);
    }

    /**
     * @throws SelectError
     */
    public function getByDeviceId(int $deviceId): Module
    {
        $this->logger->debug(sprintf('Get slave by device ID %d', $deviceId));

        return $this->fetchOne('`device_id`=?', [$deviceId], Module::class);
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Module
    {
        $this->logger->debug(sprintf('Get slave by id %s', $id));

        return $this->fetchOne('`id`=?', [$id], Module::class);
    }

    /**
     * @throws SelectError
     */
    public function getByAddress(int $address, int $masterId): Module
    {
        $this->logger->debug(sprintf('Get slave by address %d and master id %d', $address, $masterId));

        return $this->fetchOne('`address`=? AND `master_id`=?', [$address, $masterId], Module::class);
    }

    /**
     * @throws GetError
     */
    public function getFreeDeviceId(int $tryCount = 0): int
    {
        $this->logger->debug(sprintf('Get free device id. Try %d', $tryCount));

        $deviceId = mt_rand(1, AbstractHcSlave::MAX_DEVICE_ID);
        ++$tryCount;

        $count = $this->getAggregate('COUNT(`device_id`)', '`device_id`=?', [$deviceId], Module::class);

        if (!empty($count) && (int) $count[0] > 0) {
            if ($tryCount === self::MAX_GENERATE_DEVICE_ID_RETRY) {
                throw new GetError('Es konnte keine freie Device ID ermittelt werden!');
            }

            return $this->getFreeDeviceId($tryCount);
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

        $table = self::getTable($this->moduleTableName);
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
