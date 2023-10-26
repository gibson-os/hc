<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Io;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use MDO\Enum\OrderDirection;

class PortRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $moduleId, int $id): Port
    {
        return $this->fetchOne('`module_id`=? AND `id`=?', [$moduleId, $id], Port::class);
    }

    /**
     * @throws SelectError
     */
    public function getByNumber(Module $module, int $number): Port
    {
        return $this->fetchOne(
            '`module_id`=? AND `number`=?',
            [$module->getId() ?? 0, $number],
            Port::class
        );
    }

    /**
     * @throws SelectError
     *
     * @return Port[]
     */
    public function getByModule(Module $module): array
    {
        return $this->fetchAll(
            '`module_id`=?',
            [$module->getId()],
            Port::class,
            orderBy: ['`number`' => OrderDirection::ASC]
        );
    }

    /**
     * @throws SelectError
     *
     * @return Port[]
     */
    public function findByName(int $moduleId, string $name): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `name` REGEXP ?',
            [$moduleId, $this->getRegexString($name)],
            Port::class
        );
    }
}
