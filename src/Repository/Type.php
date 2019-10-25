<?php
namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Type as TypeModel;

class Type extends AbstractRepository
{
    /**
     * @param int $address
     * @return TypeModel
     * @throws SelectError
     */
    public static function getByDefaultAddress($address)
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);
        $table->appendJoin(
            '`hc_type_default_address`',
            '`' . $tableName . '`.`id`=`hc_type_default_address`.`type_id`'
        );
        $table->setWhere('`hc_type_default_address`.`address`=' . self::escape($address));
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
     * @param int $id
     * @return TypeModel
     * @throws SelectError
     */
    public static function getById(int $id): TypeModel
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);
        $table->setWhere('`id`=' . self::escape($id));
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
     * @param string $helperName
     * @return TypeModel
     * @throws SelectError
     */
    public static function getByHelperName(string $helperName): TypeModel
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
     * @param string $name
     * @param bool $onlyHcSlave
     * @param null $network
     * @return TypeModel[]
     * @throws SelectError
     */
    static function findByName($name, $onlyHcSlave = false, $network = null)
    {
        $tableName = TypeModel::getTableName();
        $table = self::getTable($tableName);

        $where = '`name` LIKE \'' . self::escape($name, false) . '%\'';

        if ($onlyHcSlave) {
            $where .= ' AND `is_hc_slave`=1';
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