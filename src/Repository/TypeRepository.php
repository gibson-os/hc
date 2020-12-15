<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Type;

class TypeRepository extends AbstractRepository
{
    /**
     * @throws DateTimeError
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

        $model = new Type();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(int $id): Type
    {
        $tableName = Type::getTableName();
        $table = self::getTable($tableName);
        $table
            ->setWhere('`id`=?')
            ->addWhereParameter($id)
            ->setLimit(1)
        ;

        if (!$table->selectPrepared()) {
            $exception = new SelectError('Kein Typ unter der ID ' . $id . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Type();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByHelperName(string $helperName): Type
    {
        $tableName = Type::getTableName();
        $table = self::getTable($tableName);
        $table->setWhere('`helper`=' . self::escape($helperName));
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('No type with helper name ' . $helperName . ' found!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new Type();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Type[]
     */
    public function findByName(string $name, bool $getHcSlaves = null, string $network = null): array
    {
        $tableName = Type::getTableName();
        $table = self::getTable($tableName);

        $where = '`name` LIKE ?';
        $table->addWhereParameter($name . '%');

        if ($getHcSlaves !== null) {
            $where .= ' AND `is_hc_slave`=?';
            $table->addWhereParameter($getHcSlaves ? 1 : 0);
        }

        if ($network !== null) {
            $where .= ' AND `network`=?';
            $table->addWhereParameter($network);
        }

        $table->setWhere($where);

        if (!$table->selectPrepared()) {
            $exception = new SelectError('No type with name ' . $name . '* found!');
            $exception->setTable($table);

            throw $exception;
        }

        $models = [];

        do {
            $model = new Type();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }
}
