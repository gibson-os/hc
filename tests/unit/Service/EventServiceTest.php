<?php declare(strict_types=1);

namespace Service;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Factory\Event\ServiceFactory;
use GibsonOS\Module\Hc\Model\Event\Element;
use GibsonOS\Module\Hc\Service\Event\AbstractEventService;
use GibsonOS\Module\Hc\Service\EventService;
use Prophecy\Prophecy\ObjectProphecy;

class EventServiceTest extends Unit
{
    /**
     * @var EventService
     */
    private $eventService;

    /**
     * @var ObjectProphecy|ServiceFactory
     */
    private $serviceFactory;

    protected function _before()
    {
        $this->serviceFactory = $this->prophesize(ServiceFactory::class);
        $this->eventService = new EventService($this->serviceFactory->reveal());
    }

    public function testFire()
    {
        $globalParams = null;

        $this->eventService->add('dent', function ($params) use (&$globalParams) {
            $globalParams = $params;
        });

        $this->eventService->fire('arthur', ['Handtuch' => true]);
        $this->assertNull($globalParams);
        $this->eventService->fire('dent', ['Handtuch' => true]);
        $this->assertEquals(['Handtuch' => true], $globalParams);
    }

    public function testRunFunction()
    {
        $element = $this->prophesize(Element::class);
        $element->getClass()
            ->shouldBeCalledOnce()
            ->willReturn('marvin')
        ;

        $eventService = $this->prophesize(AbstractEventService::class);
        $eventService->run($element->reveal())
            ->shouldBeCalledOnce()
            ->willReturn('Will end in tears')
        ;
        $this->serviceFactory->get('marvin')
            ->shouldBeCalledOnce()
            ->willReturn($eventService->reveal())
        ;

        $this->assertEquals('Will end in tears', $this->eventService->runFunction($element->reveal()));
    }
}
