<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer\Parameter;

class TypeParameter extends AbstractParameter
{
    /**
     * TypeParameter constructor.
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