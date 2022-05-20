<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Model\Sequence;

class ImageRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $moduleId, int $id): Image
    {
        return $this->fetchOne('`module`=? AND `id`=?', [$moduleId, $id], Image::class);
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
}
