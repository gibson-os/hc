<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Formatter\Bme280Formatter;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\Bme280Service;
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

        AbstractModuleTest::prophesizeWrite(
            $master,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            242,
            chr(2)
        );
        AbstractModuleTest::prophesizeWrite(
            $master,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            244,
            chr((2 << 5) | (2 << 2) | 1)
        );
        AbstractModuleTest::prophesizeRead(
            $master,
            $slave,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
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
