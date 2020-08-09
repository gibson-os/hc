<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command\Event;

use DateTime;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Module\Hc\Model\Event\Element;
use GibsonOS\Module\Hc\Repository\EventRepository;
use GibsonOS\Module\Hc\Service\Event\CodeGeneratorService;
use GibsonOS\Module\Hc\Service\EventService;

class CronjobCommand extends AbstractCommand
{
    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var CodeGeneratorService
     */
    private $codeGeneratorService;

    /**
     * @var EventService
     */
    private $eventService;

    public function __construct(
        EventRepository $eventRepository,
        CodeGeneratorService $codeGeneratorService,
        EventService $eventService
    ) {
        $this->eventRepository = $eventRepository;
        $this->codeGeneratorService = $codeGeneratorService;
        $this->eventService = $eventService;
    }

    /**
     * @throws DateTimeError
     */
    protected function run(): int
    {
        $events = $this->eventRepository->getTimeControlled(new DateTime());

        foreach ($events as $event) {
            eval($this->codeGeneratorService->generateByElements($event->getElements()));
        }

        return 0;
    }

    /**
     * @throws FileNotFound
     */
    public function runFunction(Element $element)
    {
        return $this->eventService->runFunction($element);
    }
}
