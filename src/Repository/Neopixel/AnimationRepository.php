<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Model\Sequence;

class AnimationRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(Module $module, int $id): Animation
    {
        return $this->fetchOne('`module_id`=? AND `id`=?', [$module->getId(), $id], Animation::class);
    }

    /**
     * @throws SelectError
     */
    public function getByName(Module $module, string $name): Animation
    {
        return $this->fetchOne('`module_id`=? AND `name`=?', [$module->getId(), $name], Animation::class);
    }

    /**
     * @throws SelectError
     *
     * @return Sequence[]
     */
    public function findByName(int $moduleId, string $name): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `name` REGEXP ?',
            [$moduleId, $this->getRegexString($name)],
            Sequence::class
        );
    }

    /**
     * @throws SelectError
     */
    public function getActive(Module $module): Animation
    {
        return $this->fetchOne('`module_id`=? AND `pid` IS NOT NULL', [$module->getId()], Animation::class);
    }
}
