<?php
namespace GibsonOS\Module\Hc\Config\Event\Describer;

use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\AbstractParameter;

class Method
{
    /**
     * @var AbstractParameter[]
     */
    private $parameters = [];
    /**
     * @var AbstractParameter[]
     */
    private $returnTypes = [];
    /**
     * @var string
     */
    private $title;

    /**
     * Method constructor.
     * @param string $title
     */
    public function __construct(string $title)
    {
        $this->title = $title;
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
     * @return Method
     */
    public function setParameters(array $parameters): Method
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return AbstractParameter[]|AbstractParameter[][]
     */
    public function getReturnTypes(): array
    {
        return $this->returnTypes;
    }

    /**
     * @param AbstractParameter[]|AbstractParameter[][] $returnTypes
     * @return Method
     */
    public function setReturnTypes(array $returnTypes): Method
    {
        $this->returnTypes = $returnTypes;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}