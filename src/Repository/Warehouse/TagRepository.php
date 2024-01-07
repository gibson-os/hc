<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Tag;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class TagRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $id): Tag
    {
        return $this->fetchOne('`id`=?', [$id], Tag::class);
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Tag[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll(
            '`name` REGEXP ?',
            [$this->getRegexString($name)],
            Tag::class,
        );
    }
}
