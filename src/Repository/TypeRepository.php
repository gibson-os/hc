<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Type as TypeModel;

class TypeRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     * @throws DateTimeError
     * @throws GetError
     */
    public function getByDefaultAddress(int $address): TypeModel
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);
        $table->appendJoin(
            '`hc_type_default_address`',
            '`' . $tableName . '`.`id`=`hc_type_default_address`.`type_id`'
        );
        $table->setWhere('`hc_type_default_address`.`address`=' . self::escape((string) $address));
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Typ unter der Standard Adresse ' . $address . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new TypeModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    public function getById(int $id): TypeModel
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);
        $table->setWhere('`id`=' . self::escape((string) $id));
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Typ unter der ID ' . $id . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new TypeModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByHelperName(string $helperName): TypeModel
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);
        $table->setWhere('`helper`=' . self::escape($helperName));
        $table->setLimit(1);

        if (!$table->select()) {
            $exception = new SelectError('Kein Typ mit dem Helper Namen ' . $helperName . ' bekannt!');
            $exception->setTable($table);

            throw $exception;
        }

        $model = new TypeModel();
        $model->loadFromMysqlTable($table);

        return $model;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     *
     * @return TypeModel[]
     */
    public function findByName(string $name, bool $getHcSlaves = null, string $network = null): array
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);

        $where = '`name` LIKE \'' . self::escapeWithoutQuotes($name) . '%\'';

        if ($getHcSlaves !== null) {
            $where .= ' AND `is_hc_slave`=' . ($getHcSlaves ? 1 : 0);
        }

        if ($network !== null) {
            $where .= ' AND `network`=' . self::escape($network);
        }

        $table->setWhere($where);

        if (!$table->select()) {
            $exception = new SelectError('Keine Typen mit dem Namen ' . $name . '* vorhanden!');
            $exception->setTable($table);

            throw $exception;
        }

        $models = [];

        do {
            $model = new TypeModel();
            $model->loadFromMysqlTable($table);
            $models[] = $model;
        } while ($table->next());

        return $models;
    }
}
