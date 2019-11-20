<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;
use GibsonOS\Module\Hc\Service\Event\AbstractEventService;
use stdClass;

class EventService extends AbstractService
{
    /**
     * @var AbstractEventService[]
     */
    private $services = [];

    /**
     * @var array
     */
    private $events = [];

    /**
     * @param string   $trigger
     * @param callable $function
     */
    public function add($trigger, $function)
    {
        if (!isset($this->events[$trigger])) {
            $this->events[$trigger] = [];
        }

        $this->events[$trigger][] = $function;
    }

    /**
     * @param string        $trigger
     * @param stdClass|null $params
     */
    public function fire($trigger, $params = null)
    {
        if (!isset($this->events[$trigger])) {
            return;
        }

        foreach ($this->events[$trigger] as $event) {
            $event($params);
        }
    }

    // @todo besseren ort finden. Wird eigentlich nur vom db generierten code gebraucht

    /**
     * @param string $serializedElement
     *
     * @return mixed
     */
    public function runFunction($serializedElement)
    {
        $service = $this->getService(unserialize($serializedElement));

        return $service->run();
    }

    /**
     * @param ElementModel $element
     *
     * @return AbstractEventService
     */
    private function getService(ElementModel $element)
    {
        $key =
            'masterId' . $element->getMasterId() .
            'moduleId' . $element->getModuleId() .
            $element->getClass();

        if (!isset($this->services[$key])) {
            $className = $element->getClass();
            $this->services[$key] = new $className($element);
        } else {
            $this->services[$key]->load($element);
        }

        return $this->services[$key];
    }
}
