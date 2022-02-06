<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsAttribute
{
    /**
     * @param class-string|null $type
     */
    public function __construct(private ?string $type = null, private ?string $name = null)
    {
    }

    /**
     * @return class-string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
