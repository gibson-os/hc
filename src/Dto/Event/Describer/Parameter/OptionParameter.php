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
     */
    public function __construct(string $title, array $options)
    {
        parent::__construct($title, 'option');
        $this->options = $options;
    }

    protected function getTypeConfig(): array
    {
        return [
            'options' => $this->options,
        ];
    }
}
