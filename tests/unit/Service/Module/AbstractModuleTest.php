<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Service\Module;

use Codeception\Test\Unit;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Core\Service\LoggerService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Enum\Direction;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Module\AbstractModule;
use GibsonOS\Module\Hc\Service\TransformService;
use GibsonOS\Test\Unit\Core\ModelManagerTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class AbstractModuleTest extends Unit
{
    use ModelManagerTrait;

    private ObjectProphecy|MasterService $masterService;

    private TransformService $transformService;

    private AbstractModule $abstractSlave;

    private ObjectProphecy|LogRepository $logRepository;

    protected Module $module;

    protected Master $master;

    protected function _before(): void
    {
        $this->loadModelManager();

        $this->masterService = $this->prophesize(MasterService::class);
        $this->serviceManager = new ServiceManager();
        $this->serviceManager->setInterface(LoggerInterface::class, LoggerService::class);
        $this->serviceManager->setService(MasterService::class, $this->masterService->reveal());
        $this->transformService = $this->serviceManager->get(TransformService::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->master = (new Master($this->modelWrapper->reveal()))
            ->setId(1)
            ->setAddress('42.42.42.42')
            ->setSendPort(420042)
        ;
        $this->module = (new Module($this->modelWrapper->reveal()))
            ->setAddress(4242)
            ->setId(7)
            ->setMaster($this->master)
        ;

        $this->abstractSlave = new class($this->masterService->reveal(), $this->transformService, $this->logRepository->reveal(), $this->serviceManager->get(LoggerInterface::class), $this->modelManager->reveal(), $this->modelWrapper->reveal(), $this->module) extends AbstractModule {
            private Module $module;

            public function __construct(MasterService $masterService, TransformService $transformService, LogRepository $logRepository, LoggerInterface $logger, ModelManager $modelManager, ModelWrapper $modelWrapper, Module $module)
            {
                parent::__construct($masterService, $transformService, $logRepository, $logger, $modelManager, $modelWrapper);
                $this->module = $module;
            }

            public function handshake(Module $module): Module
            {
                return $this->module;
            }
        };
    }

    public function testWrite(): void
    {
        self::prophesizeWrite(
            $this->master,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            4242,
            42,
            'Handtuch'
        );

        $this->abstractSlave->write($this->module, 42, 'Handtuch');
    }

    public function testRead(): void
    {
        self::prophesizeRead(
            $this->master,
            $this->module,
            $this->masterService,
            $this->modelWrapper,
            $this->logRepository,
            255,
            7,
            42,
            'Handtuch',
            8
        );

        $this->assertEquals(
            'Handtuch',
            $this->abstractSlave->read($this->module, 42, 8)
        );
    }

    public static function prophesizeWrite(
        Master $master,
        ObjectProphecy $masterService,
        ObjectProphecy $modelWrapper,
        ObjectProphecy $logRepository,
        int $type,
        int $slaveAddress,
        int $command,
        string $data
    ): void {
        $busMessage = (new BusMessage($master->getAddress(), $type))
            ->setCommand($command)
            ->setSlaveAddress($slaveAddress)
            ->setData($data)
            ->setPort($master->getSendPort())
            ->setWrite(true)
        ;
        $masterService->send($master, $busMessage)
            ->shouldBeCalledOnce()
        ;
        $masterService->receiveReceiveReturn($master, $busMessage)
            ->shouldBeCalledOnce()
        ;

        self::prophesizeAddLog(
            $modelWrapper,
            $logRepository,
            $type,
            $data,
            Direction::OUTPUT
        );
    }

    public static function prophesizeRead(
        Master $master,
        Module $module,
        ObjectProphecy $masterService,
        ObjectProphecy $modelWrapper,
        ObjectProphecy $logRepository,
        int $type,
        int $slaveAddress,
        int $command,
        string $data,
        int $dataLength
    ): void {
        $busMessage = (new BusMessage($master->getAddress(), MasterService::TYPE_DATA))
            ->setCommand($command)
            ->setSlaveAddress($module->getAddress())
            ->setData(chr($dataLength))
            ->setPort($master->getSendPort())
        ;
        $masterService->send($master, $busMessage)
            ->shouldBeCalledOnce()
        ;
        $masterService->receiveReadData($master, $busMessage)
            ->shouldBeCalledOnce()
            ->willReturn((new BusMessage($master->getAddress(), $type))->setData($data))
        ;

        self::prophesizeAddLog(
            $modelWrapper,
            $logRepository,
            $type,
            $data,
            Direction::INPUT
        );
    }

    public static function prophesizeAddLog(
        ObjectProphecy $modelWrapper,
        ObjectProphecy $logRepository,
        int $type,
        string $data,
        Direction $direction
    ): void {
        $logRepository->create($type, $data, $direction)
            ->shouldBeCalledOnce()
            ->willReturn(new Log($modelWrapper->reveal()))
        ;
    }
}
