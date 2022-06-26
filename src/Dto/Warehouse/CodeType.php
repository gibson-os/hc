<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse;

use GibsonOS\Core\Model\AutoCompleteModelInterface;
use JsonSerializable;

class CodeType implements JsonSerializable, AutoCompleteModelInterface
{
    public function __construct(private readonly Code $code)
    {
    }

    public function getAutoCompleteId(): string
    {
        return $this->code->name;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->code->name,
            'name' => $this->code->value,
        ];
    }
}
