<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer\Parameter;

class OptionParameter extends AbstractParameter
{
    /**
     * @var array
     */
    private $options;

    /**
     * IntParameter constructor.
     * @param string $title
     * @param array $options
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
            'options' => $this->options
        ];
    }
}