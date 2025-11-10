<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use DateTime;
use Exception;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type\DefaultAddress;
use JsonException;
use MDO\Dto\Query\Where;
use MDO\Dto\Record;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class MasterRepository extends AbstractRepository
{
    private const MIN_PORT = 42001;

    private const MAX_PORT = 42999;

    private const FIRST_SLAVE_ADDRESS = 8;

    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(DefaultAddress::class)]
        private readonly string $defaultAddressTableName,
        #[GetTableName(Module::class)]
        private readonly string $moduleTableName,
    ) {
        parent::__construct($repositoryWrapper);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getById(int $id): Master
    {
        return $this->fetchOne('`id`=?', [$id], Master::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getByAddress(string $address, string $protocol): Master
    {
        return $this->fetchOne('`protocol`=? AND `address`=?', [$protocol, $address], Master::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
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
        $model = (new Master($this->getRepositoryWrapper()->getModelWrapper()))
            ->setName($name)
            ->setProtocol($protocol)
            ->setAddress($address)
            ->setSendPort($this->findFreePort())
            ->setAdded(new DateTime())
        ;
        $this->getRepositoryWrapper()->getModelManager()->save($model);

        return $model;
    }

    /**
     * @throws ClientException
     * @throws RecordException
     * @throws SelectError
     */
    public function getNextFreeAddress(int $masterId): int
    {
        $moduleSelectQuery = $this->getSelectQuery($this->moduleTableName)
            ->addWhere(new Where('`master_id`=?', [$masterId]))
            ->setSelects(['address' => '`address`'])
        ;
        $typeDefaultAddressSelectQuery = $this->getSelectQuery($this->defaultAddressTableName)
            ->setSelects(['address' => '`address`'])
        ;

        $result = $this->getRepositoryWrapper()->getClient()->execute(
            sprintf(
                'SELECT (%s) UNION ALL (%s) ORDER BY `address`',
                $moduleSelectQuery->getQuery(),
                $typeDefaultAddressSelectQuery->getQuery(),
            ),
            $moduleSelectQuery->getWhereParameters(),
        );
        $reservedAddresses = array_map(
            static fn (Record $record): int => (int) $record->get('address')->getValue(),
            iterator_to_array($result->iterateRecords()),
        );
        $address = self::FIRST_SLAVE_ADDRESS;

        while (in_array($address, $reservedAddresses)) {
            ++$address;

            if ($address > Module::MAX_ADDRESS) {
                throw new SelectError('Keine freie Adresse vorhanden!');
            }
        }

        return $address;
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Master[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll('`name` LIKE ?', [$name . '%'], Master::class);
    }

    private function findFreePort(): int
    {
        $port = mt_rand(self::MIN_PORT, self::MAX_PORT);
        $aggregations = $this->getAggregations(
            ['count' => 'COUNT(`id`)'],
            Master::class,
            '`send_port`=?',
            [$port],
        );

        if ((int) $aggregations->get('count')->getValue() > 0) {
            return $this->findFreePort();
        }

        return $port;
    }
}
