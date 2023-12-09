<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse\Label;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Template;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class TemplateRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     */
    public function getById(int $id): Template
    {
        return $this->fetchOne('`id`=?', [$id], Template::class);
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Template[]
     */
    public function findByName(string $name): array
    {
        return $this->fetchAll(
            '`name` REGEXP ?',
            [$this->getRegexString($name)],
            Template::class
        );
    }
}
