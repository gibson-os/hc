<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Module\AbstractHcModule;
use JsonException;
use MDO\Dto\Query\Where;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use MDO\Query\DeleteQuery;
use Psr\Log\LoggerInterface;
use ReflectionException;

class ModuleRepository extends AbstractRepository
{
    private const MAX_GENERATE_DEVICE_ID_RETRY = 10;

    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        private readonly LoggerInterface $logger,
        #[GetTableName(Module::class)]
        private readonly string $moduleTableName
    ) {
        parent::__construct($repositoryWrapper);
    }

    public function create(string $name, Type $type): Module
    {
        $this->logger->debug(sprintf('Create module with name %d and type %d', $name, $type->getName()));

        return (new Module($this->getRepositoryWrapper()->getModelWrapper()))
            ->setName($name)
            ->setType($type)
        ;
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Module[]
     */
    public function findByName(string $name, int $typeId = null): array
    {
        $this->logger->debug(sprintf('Find slave with name %s and type id %d', $name, $typeId ?? 0));

        $where = '`name` LIKE ?';
        $parameters = [$name . '%'];

        if ($typeId !== null) {
            $where .= ' AND `type_id`=?';
            $parameters[] = $typeId;
        }

        return $this->fetchAll($where, $parameters, Module::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getByDeviceId(int $deviceId): Module
    {
        $this->logger->debug(sprintf('Get slave by device ID %d', $deviceId));

        return $this->fetchOne('`device_id`=?', [$deviceId], Module::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getById(int $id): Module
    {
        $this->logger->debug(sprintf('Get slave by id %s', $id));

        return $this->fetchOne('`id`=?', [$id], Module::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getByAddress(int $address, int $masterId): Module
    {
        $this->logger->debug(sprintf('Get module by address %d and master id %d', $address, $masterId));

        return $this->fetchOne('`address`=? AND `master_id`=?', [$address, $masterId], Module::class);
    }

    /**
     * @throws ClientException
     * @throws GetError
     * @throws RecordException
     * @throws SelectError
     */
    public function getFreeDeviceId(int $tryCount = 0): int
    {
        $this->logger->debug(sprintf('Get free device id. Try %d', $tryCount));

        $deviceId = mt_rand(1, AbstractHcModule::MAX_DEVICE_ID);
        ++$tryCount;

        $aggregations = $this->getAggregations(
            ['count' => 'COUNT(`device_id`)'],
            Module::class,
            '`device_id`=?',
            [$deviceId],
        );

        if ((int) $aggregations->get('count')->getValue() > 0) {
            if ($tryCount === self::MAX_GENERATE_DEVICE_ID_RETRY) {
                throw new GetError('Es konnte keine freie Device ID ermittelt werden!');
            }

            return $this->getFreeDeviceId($tryCount);
        }

        return $deviceId;
    }

    /**
     * @param int[] $ids
     */
    public function deleteByIds(array $ids): bool
    {
        $this->logger->debug(sprintf('Delete slaves by IDs %s', implode(', ', $ids)));

        $deleteQuery = (new DeleteQuery($this->getTable($this->moduleTableName)))
            ->addWhere(new Where(
                sprintf(
                    '`id` IN (%s)',
                    $this->getRepositoryWrapper()->getSelectService()->getParametersString($ids),
                ),
                $ids,
            ))
        ;

        try {
            $this->getRepositoryWrapper()->getClient()->execute($deleteQuery);
        } catch (ClientException) {
            return false;
        }

        return true;
    }
}
