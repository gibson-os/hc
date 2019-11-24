<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer;

use GibsonOS\Module\Hc\Dto\Event\Describer\Parameter\AbstractParameter;

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
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

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
     */
    public function setParameters(array $parameters): Trigger
    {
        $this->parameters = $parameters;

        return $this;
    }
}
