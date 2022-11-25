<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse\Label\Element;

use GibsonOS\Core\Model\AutoCompleteModelInterface;

class ElementType implements \JsonSerializable, AutoCompleteModelInterface
{
    public function __construct(private readonly Type $type)
    {
    }

    public function getAutoCompleteId(): string
    {
        return $this->type->name;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->type->name,
            'name' => $this->type->value,
        ];
    }
}
