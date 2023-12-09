<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class AnimationRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $moduleId, int $id): Animation
    {
        return $this->fetchOne('`module_id`=? AND `id`=?', [$moduleId, $id], Animation::class);
    }

    /**
     * @throws SelectError
     */
    public function getByName(Module $module, string $name): Animation
    {
        return $this->fetchOne('`module_id`=? AND `name`=?', [$module->getId(), $name], Animation::class);
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Animation[]
     */
    public function findByName(int $moduleId, string $name): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `name` REGEXP ?',
            [$moduleId, $this->getRegexString($name)],
            Animation::class
        );
    }

    /**
     * @throws SelectError
     */
    public function getStarted(Module $module): Animation
    {
        return $this->fetchOne(
            '`module_id`=? AND `started`=?',
            [$module->getId(), 1],
            Animation::class
        );
    }

    /**
     * @throws SelectError
     */
    public function getTransmitted(Module $module): Animation
    {
        return $this->fetchOne(
            '`module_id`=? AND `transmitted`=?',
            [$module->getId(), 1],
            Animation::class
        );
    }
}
