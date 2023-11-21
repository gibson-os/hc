<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Formatter\Bme280Formatter;
use GibsonOS\Module\Hc\Mapper\Bme280Mapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\Bme280Service;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class Bme280ServiceTest extends Unit
{
    use ModelManagerTrait;

    private Bme280Service $bme280Service;

    private ObjectProphecy|MasterService $masterService;

    private ObjectProphecy|LogRepository $logRepository;

    private ObjectProphecy|Bme280Formatter $bme280Mapper;

    protected function _before()
    {
        $this->loadModelManager();

        $this->masterService = $this->prophesize(MasterService::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->bme280Mapper = $this->prophesize(Bme280Mapper::class);
        $this->bme280Service = new Bme280Service(
            $this->masterService->reveal(),
            new TransformService(),
            $this->logRepository->reveal(),
            $this->bme280Mapper->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->modelManager->reveal(),
            $this->modelWrapper->reveal(),
        );
    }

    /**
     * @dataProvider getHandshakeData
     */
    public function testHandshake(?string $config): void
    {
        $master = (new Master($this->modelWrapper->reveal()))
            ->setId(1)
            ->setAddress('42.42.42.42')
            ->setSendPort(420042)
        ;
        $type = (new Type($this->modelWrapper->reveal()))
            ->setId(7)
            ->setHelper('prefect')
        ;
        $module = (new Module($this->modelWrapper->reveal()))
            ->setAddress(42)
            ->setDeviceId(4242)
            ->setId(7)
            ->setType($type)
            ->setMaster($master)
            ->setConfig($config)
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
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            136,
            'c1',
            24
        );
        AbstractModuleTest::prophesizeRead(
            $master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            161,
            'c2',
            1
        );
        AbstractModuleTest::prophesizeRead(
            $master,
            $module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            42,
            225,
            'c3',
            7
        );

        $this->bme280Service->handshake($module);
    }

    public function getHandshakeData(): array
    {
        return [
            'Empty Config' => [null],
        ];
    }
}
