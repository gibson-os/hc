<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence as SequenceModel;
use GibsonOS\Module\Hc\Model\Type;
use mysqlTable;
use stdClass;

class Sequence extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public static function getById(int $id): SequenceModel
    {
        $table = self::getTable(SequenceModel::getTableName());
        $where = '`id`=' . self::escape((string) $id);

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        $record = $table->connection->fetchObject();

        if (!$record instanceof stdClass) {
            throw new SelectError();
        }

        return self::getModel($record);
    }

    /**
     * @throws SelectError
     */
    public static function getByName(Module $module, string $name, int $type = null): SequenceModel
    {
        $module->loadType();
        $table = self::getTable(SequenceModel::getTableName());
        $where =
            '`name`=' . self::escape($name) . ' AND ' .
            '`type_id`=' . self::escape((string) $module->getType()->getId()) . ' AND ' .
            '(`module_id`=' . self::escape((string) $module->getId()) . ' OR `module_id` IS NULL)'
        ;

        if ($type !== null) {
            $where .= ' AND `type`=' . self::escape((string) $type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        $sequence = $table->connection->fetchObject();

        if (!$sequence instanceof stdClass) {
            throw new SelectError();
        }

        return self::getModel($sequence);
    }

    /**
     * @throws SelectError
     *
     * @return SequenceModel[]
     */
    public static function getByModule(Module $module, int $type = null): array
    {
        $module->loadType();
        $table = self::getTable(SequenceModel::getTableName());
        $where =
            '`module_id`=' . self::escape((string) $module->getId()) . ' AND ' .
            '`type_id`=' . self::escape((string) $module->getType()->getId())
        ;

        if ($type !== null) {
            $where .= ' AND `type`=' . self::escape((string) $type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        return self::getModels($table);
    }

    /**
     * @throws SelectError
     */
    public static function getByType(Type $typeModel, int $type = null): array
    {
        $table = self::getTable(SequenceModel::getTableName());
        $where = '`type_id`=' . self::escape((string) $typeModel->getId());

        if ($type !== null) {
            $where .= ' AND `type`=' . self::escape((string) $type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        return self::getModels($table);
    }

    /**
     * @return SequenceModel[]
     */
    private static function getModels(mysqlTable $table): array
    {
        $models = [];

        foreach ($table->connection->fetchObjectList() as $sequence) {
            $models[] = self::getModel($sequence);
        }

        return $models;
    }

    private static function getModel(stdClass $sequence): SequenceModel
    {
        return (new SequenceModel())
            ->setId($sequence->id)
            ->setName($sequence->name)
            ->setTypeId($sequence->type_id)
            ->setModuleId($sequence->module_id)
            ->setType($sequence->type)
        ;
    }
}
