<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Attribute;

use Attribute;
use GibsonOS\Module\Hc\Mapper\AttributeMapperInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AttributeMapper
{
    /**
     * @param class-string<AttributeMapperInterface> $attributeMapper
     */
    public function __construct(private string $attributeMapper)
    {
    }

    /**
     * @return class-string<AttributeMapperInterface>
     */
    public function getAttributeMapper(): string
    {
        return $this->attributeMapper;
    }
}
