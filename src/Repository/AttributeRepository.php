<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Type;

class AttributeRepository extends AbstractRepository
{
    public function __construct()
    {
    }

    /**
     * @throws SelectError
     *
     * @return Attribute[]
     */
    public function getByType(Type $type, string $typeString): array
    {
        return $this->fetchAll(
            '`type_id`=? AND `type`=?',
            [$type->getId(), $typeString],
            Attribute::class
        );
    }
}
