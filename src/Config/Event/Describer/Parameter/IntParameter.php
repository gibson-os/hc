<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer\Parameter;

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
     * @param string $title
     */
    public function __construct(string $title)
    {
        parent::__construct($title, 'int');
    }

    /**
     * @param int|null $min
     * @param int|null $max
     * @return IntParameter
     */
    public function setRange(?int $min, int $max = null): IntParameter
    {
        $this->min = $min;
        $this->max = $max;

        return $this;
    }

    /**
     * @return array
     */
    protected function getTypeConfig(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max
        ];
    }
}