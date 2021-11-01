<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Type;
use mysqlTable;

/**
 * @method Type   fetchOne(string $where, array $parameters, string $abstractModelClassName = AbstractModel::class)
 * @method Type[] fetchAll(string $where, array $parameters, string $abstractModelClassName = AbstractModel::class, int $limit = null, int $offset = null, string $orderBy = null)
 * @method Type   getModel(mysqlTable $table, string $abstractModelClassName)
 */
class TypeRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getByDefaultAddress(int $address): Type
    {
        $tableName = Type::getTableName();
        $defaultAddressTableName = Type\DefaultAddress::getTableName();
        $table = self::getTable($tableName);
        $table
            ->appendJoin(
                '`' . $defaultAddressTableName . '`',
                '`' . $tableName . '`.`id`=`hc_type_default_address`.`type_id`'
            )
            ->setWhere('`' . $defaultAddressTableName . '`.`address`=?')
            ->addWhereParameter($address)
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError(sprintf('No type under default address %d!', $address));
            $exception->setTable($table);

            throw $exception;
        }

        return $this->getModel($table, Type::class);
    }

    /**
     * @throws SelectError
     */
    public function getById(int $id): Type
    {
        return $this->fetchOne('`id`=?', [$id], Type::class);
    }

    /**
     * @throws SelectError
     */
    public function getByHelperName(string $helperName): Type
    {
        return $this->fetchOne('`helper`=?', [$helperName], Type::class);
    }

    /**
     * @throws SelectError
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
