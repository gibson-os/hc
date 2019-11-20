<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

class BoolParameter extends AbstractParameter
{
    /**
     * BoolParameter constructor.
     *
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
