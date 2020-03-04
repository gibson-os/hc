<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Factory\Event\ServiceFactory;
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
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

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

    /**
     * @throws FileNotFound
     */
    public function runFunction(ElementModel $element)
    {
        $service = $this->serviceFactory->get($element->getClass());

        return $service->run($element);
    }
}
