<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

use GibsonOS\Core\Dto\Event\Describer\Parameter\AbstractParameter;

class TypeParameter extends AbstractParameter
{
    public function __construct(string $title = 'Typ')
    {
        parent::__construct($title, 'type');
    }

    protected function getTypeConfig(): array
    {
        return [];
    }
}
