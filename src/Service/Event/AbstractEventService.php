<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Module\Hc\Model\Event\Element;
use GibsonOS\Module\Hc\Service\Event\Describer\DescriberInterface;

abstract class AbstractEventService
{
    /**
     * @var DescriberInterface
     */
    private $describer;

    /**
     * AbstractEvent constructor.
     */
    public function __construct(DescriberInterface $describer)
    {
        $this->describer = $describer;
    }

    public function run(Element $element)
    {
        $method = $element->getMethod();

        if (!isset($this->describer->getMethods()[$method])) {
            // @todo throw exception
        }

        return $this->{$method}(...$this->getParams($element));
    }

    protected function getParams(Element $element): array
    {
        $params = $element->getParams();

        return $params === null ? [] : unserialize($params);
    }
}
