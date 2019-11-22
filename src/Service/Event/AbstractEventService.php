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
     *
     * @param DescriberInterface $describer
     */
    public function __construct(DescriberInterface $describer)
    {
        $this->describer = $describer;
    }

    /**
     * @param Element $element
     *
     * @return mixed
     */
    public function run(Element $element)
    {
        $function = $element->getFunction();

        if (!isset($this->describer->getMethods()[$function])) {
            // @todo throw exception
        }

        return $this->{$function}();
    }

    /**
     * @param Element $element
     *
     * @return mixed
     */
    protected function getParams(Element $element)
    {
        return unserialize($element->getParams());
    }
}
