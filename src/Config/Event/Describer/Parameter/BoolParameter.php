<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer\Parameter;

class BoolParameter extends AbstractParameter
{
    /**
     * BoolParameter constructor.
     * @param string $title
     */
    public function __construct(string $title)
    {
        parent::__construct($title, 'bool');
    }

    /**
     * @return array
     */
    protected function getTypeConfig(): array
    {
        return [];
    }
}