<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Warehouse;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Module\Hc\Dto\Warehouse\Code;
use GibsonOS\Module\Hc\Dto\Warehouse\CodeType;

class CodeTypeAutoComplete implements AutoCompleteInterface
{
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $codes = [];

        foreach (Code::cases() as $case) {
            if ($namePart === '' || mb_strpos($case->value, $namePart) === 0) {
                $codes[] = new CodeType($case);
            }
        }

        return $codes;
    }

    public function getById(string $id, array $parameters): CodeType
    {
        return new CodeType(constant(sprintf('%s::%s', Code::class, $id)));
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.warehouse.model.CodeType';
    }

    public function getValueField(): string
    {
        return 'id';
    }

    public function getDisplayField(): string
    {
        return 'name';
    }
}
