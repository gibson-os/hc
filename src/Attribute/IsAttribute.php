<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsAttribute
{
    public function __construct(private ?string $name = null)
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
