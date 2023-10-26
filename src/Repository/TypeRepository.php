<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Type;
use JsonException;
use MDO\Dto\Query\Join;
use MDO\Dto\Query\Where;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class TypeRepository extends AbstractRepository
{
    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(Type::class)]
        private readonly string $typeTableName,
        #[GetTableName(Type\DefaultAddress::class)]
        private readonly string $defaultAddressTableName,
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
    public function getByDefaultAddress(int $address): Type
    {
        $selectQuery = $this->getSelectQuery($this->typeTableName, 't')
            ->addJoin(new Join(
                $this->getTable($this->defaultAddressTableName),
                'da',
                '`t`.`id`=`da`.`type_id`',
            ))
            ->addWhere(new Where('`da`.`address`=?', [$address]))
            ->setLimit(1)
        ;

        return $this->getModel($selectQuery, Type::class);
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getById(int $id): Type
    {
        return $this->fetchOne('`id`=?', [$id], Type::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getByHelperName(string $helperName): Type
    {
        return $this->fetchOne('`helper`=?', [$helperName], Type::class);
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Type[]
     */
    public function findByName(string $name, bool $getHcSlaves = null, string $network = null): array
    {
        $where = '`name` LIKE ?';
        $parameters = [$name . '%'];

        if ($getHcSlaves !== null) {
            $where .= ' AND `is_hc_slave`=?';
            $parameters[] = $getHcSlaves ? 1 : 0;
        }

        if ($network !== null) {
            $where .= ' AND `network`=?';
            $parameters[] = $network;
        }

        return $this->fetchAll($where, $parameters, Type::class);
    }
}
