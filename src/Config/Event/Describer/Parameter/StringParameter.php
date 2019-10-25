<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer\Parameter;

class StringParameter extends AbstractParameter
{
    /**
     * StringParameter constructor.
     * @param string $title
     */
    public function __construct(string $title)
    {
        parent::__construct($title, 'string');
    }

    /**
     * @return array
     */
    protected function getTypeConfig(): array
    {
        return [];
    }
}