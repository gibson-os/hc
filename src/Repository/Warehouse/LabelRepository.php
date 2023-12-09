<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class LabelRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $id): Label
    {
        return $this->fetchOne('`id`=?', [$id], Label::class);
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Label[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll(
            '`name` REGEXP ?',
            [$this->getRegexString($name)],
            Label::class
        );
    }
}
