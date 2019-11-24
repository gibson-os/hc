<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

class IntParameter extends AbstractParameter
{
    /**
     * @var int|null
     */
    private $min;

    /**
     * @var int|null
     */
    private $max;

    /**
     * IntParameter constructor.
     */
    public function __construct(string $title)
    {
        parent::__construct($title, 'int');
    }

    public function setRange(?int $min, int $max = null): IntParameter
    {
        $this->min = $min;
        $this->max = $max;

        return $this;
    }

    protected function getTypeConfig(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
}
