<?php
declare(strict_types=1);

namespace Gibson\Test\Unit\Service\Slave;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Formatter\Bme280Formatter;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\Bme280Service;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class Bme280ServiceTest extends Unit
{
    use ProphecyTrait;

    /**
     * @var Bme280Service
     */
    private $bme280Service;

    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var ObjectProphecy|TransformService
     */
    private $transformService;

    /**
     * @var ObjectProphecy|LogRepository
     */
    private $logRepository;

    /**
     * @var ObjectProphecy|Bme280Formatter
     */
    private $bme280Formatter;

    protected function _before()
    {
        $this->masterService = $this->prophesize(MasterService::class);
        $this->transformService = new TransformService();
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->bme280Formatter = $this->prophesize(Bme280Formatter::class);
        $this->bme280Service = new Bme280Service(
            $this->masterService->reveal(),
            $this->transformService,
            $this->logRepository->reveal(),
            $this->bme280Formatter->reveal()
        );
    }

    /**
     * @dataProvider getHandshakeData
     */
    public function testHandshake(?string $config): void
    {
        $master = $this->prophesize(Master::class);
        $slave = $this->prophesize(Module::class);
        $slave->getConfig()
            ->shouldBeCalledOnce()
            ->willReturn($config)
        ;

        AbstractSlaveTest::prophesizeWrite(
            $master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            242,
            chr(2)
        );
        AbstractSlaveTest::prophesizeWrite(
            $master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            244,
            chr((2 << 5) | (2 << 2) | 1)
        );
        AbstractSlaveTest::prophesizeRead(
            $master,
            $slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            42,
            136,
            '',
            24
        );

        $this->bme280Service->handshake($slave->reveal());
    }

    public function getHandshakeData(): array
    {
        return [
            'Empty Config' => [null],
        ];
    }
}
