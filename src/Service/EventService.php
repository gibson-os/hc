<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;
use GibsonOS\Module\Hc\Service\Event\AbstractEventService;

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
     * @param callable $function
     */
    public function add(string $trigger, $function): void
    {
        if (!isset($this->events[$trigger])) {
            $this->events[$trigger] = [];
        }

        $this->events[$trigger][] = $function;
    }

    public function fire(string $trigger, array $params = null): void
    {
        if (!isset($this->events[$trigger])) {
            return;
        }

        foreach ($this->events[$trigger] as $event) {
            $event($params);
        }
    }

    // @todo besseren ort finden. Wird eigentlich nur vom db generierten code gebraucht

    public function runFunction(string $serializedElement)
    {
        $element = unserialize($serializedElement);
        $service = $this->getService($element);

        return $service->run($element);
    }

    private function getService(ElementModel $element): AbstractEventService
    {
        $key =
            'masterId' . $element->getMasterId() .
            'moduleId' . $element->getModuleId() .
            $element->getClass();

        if (!isset($this->services[$key])) {
            $className = $element->getClass();
            $this->services[$key] = new $className($element);
        }

        return $this->services[$key];
    }
}
