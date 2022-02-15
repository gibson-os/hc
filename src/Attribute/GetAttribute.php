<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Attribute;

use Attribute;
use GibsonOS\Core\Attribute\GetObject;
use GibsonOS\Module\Hc\Service\Attribute\AttributeMapperAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class GetAttribute extends GetObject
{
    public function getAttributeServiceName(): string
    {
        return AttributeMapperAttribute::class;
    }
}
