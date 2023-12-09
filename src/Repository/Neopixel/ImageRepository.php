<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class ImageRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $moduleId, int $id): Image
    {
        return $this->fetchOne('`module_id`=? AND `id`=?', [$moduleId, $id], Image::class);
    }

    /**
     * @throws SelectError
     */
    public function getByName(Module $module, string $name): Image
    {
        return $this->fetchOne(
            '`module_id`=? AND `name`=?',
            [$module->getId(), $name],
            Image::class
        );
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Image[]
     */
    public function findByName(int $moduleId, string $name): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `name` REGEXP ?',
            [$moduleId, $this->getRegexString($name)],
            Image::class
        );
    }
}
