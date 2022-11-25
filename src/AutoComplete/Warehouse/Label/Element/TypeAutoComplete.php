<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Warehouse\Label\Element;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Module\Hc\Dto\Warehouse\Label\Element\ElementType;
use GibsonOS\Module\Hc\Dto\Warehouse\Label\Element\Type;

class TypeAutoComplete implements AutoCompleteInterface
{
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $codes = [];

        foreach (Type::cases() as $case) {
            if ($namePart === '' || \mb_strpos($case->value, $namePart) === 0) {
                $codes[] = new ElementType($case);
            }
        }

        return $codes;
    }

    public function getById(string $id, array $parameters): ElementType
    {
        return new ElementType(constant(sprintf('%s::%s', Type::class, $id)));
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.warehouse.model.label.ElementType';
    }
}
