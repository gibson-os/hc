<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

class OptionParameter extends AbstractParameter
{
    /**
     * @var array
     */
    private $options;

    /**
     * IntParameter constructor.
     *
     * @param string $title
     * @param array  $options
     */
    public function __construct(string $title, array $options)
    {
        parent::__construct($title, 'option');
        $this->options = $options;
    }

    /**
     * @return array
     */
    protected function getTypeConfig(): array
    {
        return [
            'options' => $this->options,
        ];
    }
}
