<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer;

use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\AbstractParameter;

class Trigger
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var AbstractParameter[]
     */
    private $parameters = [];

    /**
     * Trigger constructor.
     * @param string $title
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return AbstractParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param AbstractParameter[] $parameters
     * @return Trigger
     */
    public function setParameters(array $parameters): Trigger
    {
        $this->parameters = $parameters;
        return $this;
    }
}