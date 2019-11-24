<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer;

use GibsonOS\Module\Hc\Dto\Event\Describer\Parameter\AbstractParameter;

class Method
{
    /**
     * @var AbstractParameter[]
     */
    private $parameters = [];

    /**
     * @var AbstractParameter[]|AbstractParameter[][]
     */
    private $returnTypes = [];

    /**
     * @var string
     */
    private $title;

    /**
     * Method constructor.
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
     */
    public function setReturnTypes(array $returnTypes): Method
    {
        $this->returnTypes = $returnTypes;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
