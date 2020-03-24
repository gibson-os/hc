<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence as SequenceModel;
use GibsonOS\Module\Hc\Model\Type;
use mysqlTable;
use stdClass;

class SequenceRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $id): SequenceModel
    {
        $table = $this->getTable(SequenceModel::getTableName());
        $where = '`id`=' . $this->escape((string) $id);

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        $record = $table->connection->fetchObject();

        if (!$record instanceof stdClass) {
            throw new SelectError();
        }

        return $this->getModel($record);
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getByName(Module $module, string $name, int $type = null): SequenceModel
    {
        $table = $this->getTable(SequenceModel::getTableName());
        $where =
            '`name`=' . $this->escape($name) . ' AND ' .
            '`type_id`=' . $this->escape((string) $module->getType()->getId()) . ' AND ' .
            '(`module_id`=' . $this->escape((string) $module->getId()) . ' OR `module_id` IS NULL)'
        ;

        if ($type !== null) {
            $where .= ' AND `type`=' . $this->escape((string) $type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        $sequence = $table->connection->fetchObject();

        if (!$sequence instanceof stdClass) {
            throw new SelectError();
        }

        return $this->getModel($sequence);
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return SequenceModel[]
     */
    public function getByModule(Module $module, int $type = null): array
    {
        $table = $this->getTable(SequenceModel::getTableName());
        $where =
            '`module_id`=' . $this->escape((string) $module->getId()) . ' AND ' .
            '`type_id`=' . $this->escape((string) $module->getType()->getId())
        ;

        if ($type !== null) {
            $where .= ' AND `type`=' . $this->escape((string) $type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        return $this->getModels($table);
    }

    /**
     * @throws SelectError
     */
    public function getByType(Type $typeModel, int $type = null): array
    {
        $table = $this->getTable(SequenceModel::getTableName());
        $where = '`type_id`=' . $this->escape((string) $typeModel->getId());

        if ($type !== null) {
            $where .= ' AND `type`=' . $this->escape((string) $type);
        }

        $table->setWhere($where);

        if (!$table->select(false)) {
            throw new SelectError();
        }

        return $this->getModels($table);
    }

    /**
     * @return SequenceModel[]
     */
    private function getModels(mysqlTable $table): array
    {
        $models = [];

        foreach ($table->connection->fetchObjectList() as $sequence) {
            $models[] = $this->getModel($sequence);
        }

        return $models;
    }

    private function getModel(stdClass $sequence): SequenceModel
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
