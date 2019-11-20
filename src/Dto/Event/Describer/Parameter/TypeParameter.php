<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

class TypeParameter extends AbstractParameter
{
    /**
     * TypeParameter constructor.
     *
     * @param string $title
     */
    public function __construct(string $title = 'Typ')
    {
        parent::__construct($title, 'type');
    }

    /**
     * @return array
     */
    protected function getTypeConfig(): array
    {
        return [];
    }
}
