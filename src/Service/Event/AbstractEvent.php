<?php
namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;
use GibsonOS\Module\Hc\Service\Event\Describer\DescriberInterface;

abstract class AbstractEvent
{
    /**
     * @var ElementModel
     */
    private $element;
    /**
     * @var DescriberInterface
     */
    private $describer;

    /**
     * AbstractEvent constructor.
     * @param ElementModel $element
     * @param DescriberInterface $describer
     */
    public function __construct(ElementModel $element, DescriberInterface $describer)
    {
        $this->load($element);
        $this->describer = $describer;
    }

    /**
     * @param ElementModel $element
     */
    public function load(ElementModel $element)
    {
        $this->element = $element;
    }

    /**
     * @return mixed
     */
    public function run()
    {
        $function = $this->element->getFunction();

        if (!isset($this->describer->getMethods()[$function])) {
            // @todo throw exception
        }

        return $this->{$function}();
    }

    protected function getParams()
    {
        return unserialize($this->element->getParams());
    }
}