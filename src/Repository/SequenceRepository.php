<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Type;

class SequenceRepository extends AbstractRepository
{
    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById(int $id): Sequence
    {
        $model = $this->fetchOne('`id`=?', [$id], Sequence::class);

        if (!$model instanceof Sequence) {
            throw new SelectError();
        }

        return $model;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getByName(Module $module, string $name, int $type = null): Sequence
    {
        $where = '`name`=? AND `type_id`=? AND (`module_id`=? OR `module_id` IS NULL)';
        $parameters = [$name, $module->getType()->getId(), $module->getId()];

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        $model = $this->fetchOne($where, $parameters, Sequence::class);

        if (!$model instanceof Sequence) {
            throw new SelectError();
        }

        return $model;
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Sequence[]
     */
    public function findByName(Module $module, string $name, int $type = null): array
    {
        $where = '`name` REGEXP ? AND `type_id`=? AND (`module_id`=? OR `module_id` IS NULL)';
        $parameters = [$this->getRegexString($name), $module->getType()->getId(), $module->getId()];

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        return $this->fetchAll($where, $parameters, Sequence::class);
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Sequence[]
     */
    public function getByModule(Module $module, int $type = null): array
    {
        $where = '`module_id`=? AND `type_id`=?';
        $parameters = [$module->getId(), $module->getType()->getId()];

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        return $this->fetchAll($where, $parameters, Sequence::class);
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return Sequence[]
     */
    public function getByType(Type $typeModel, int $type = null): array
    {
        $where = '`type_id`=?';
        $parameters = [$typeModel->getId()];

        if ($type !== null) {
            $where .= ' AND `type`=?';
            $parameters[] = $type;
        }

        return $this->fetchAll($where, $parameters, Sequence::class);
    }
}
