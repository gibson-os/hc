<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Type;
use mysqlTable;
use stdClass;

class SequenceRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $id): Sequence
    {
        $table = $this
            ->getTable(Sequence::getTableName())
            ->setWhere('`id`=?')
            ->addWhereParameter($id)
        ;

        if (!$table->selectPrepared(false)) {
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
    public function getByName(Module $module, string $name, int $type = null): Sequence
    {
        $table = $this
            ->getTable(Sequence::getTableName())
            ->setWhereParameters([$name, $module->getType()->getId(), $module->getId()])
        ;
        $where = '`name`=? AND `type_id`=? AND (`module_id`=? OR `module_id` IS NULL)';

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $table->addWhereParameter($type);
        }

        $table->setWhere($where);

        if (!$table->selectPrepared(false)) {
            throw new SelectError();
        }

        $sequence = $table->connection->fetchObject();

        if (!$sequence instanceof stdClass) {
            throw new SelectError();
        }

        return $this->getModel($sequence);
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Sequence[]
     */
    public function findByName(Module $module, string $name, int $type = null): array
    {
        $table = $this
            ->getTable(Sequence::getTableName())
            ->setWhereParameters([$this->getRegexString($name), $module->getType()->getId(), $module->getId()])
        ;
        $where = '`name` REGEXP ? AND `type_id`=? AND (`module_id`=? OR `module_id` IS NULL)';

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $table->addWhereParameter($type);
        }

        $table->setWhere($where);

        if (!$table->selectPrepared(false)) {
            throw new SelectError();
        }

        return $this->getModels($table);
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Sequence[]
     */
    public function getByModule(Module $module, int $type = null): array
    {
        $table = $this->getTable(Sequence::getTableName());
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
        $table = $this->getTable(Sequence::getTableName());
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
     * @return Sequence[]
     */
    private function getModels(mysqlTable $table): array
    {
        $models = [];

        foreach ($table->connection->fetchObjectList() as $sequence) {
            $models[] = $this->getModel($sequence);
        }

        return $models;
    }

    private function getModel(stdClass $sequence): Sequence
    {
        return (new Sequence())
            ->setId((int) $sequence->id)
            ->setName($sequence->name)
            ->setTypeId((int) $sequence->type_id ?: null)
            ->setModuleId((int) $sequence->module_id ?: null)
            ->setType((int) $sequence->type ?: null)
        ;
    }
}
