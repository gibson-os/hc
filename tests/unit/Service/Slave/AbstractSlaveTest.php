<?php declare(strict_types=1);

namespace Service\Slave;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\Prophecy\ObjectProphecy;

class AbstractSlaveTest extends Unit
{
    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var ObjectProphecy|TransformService
     */
    private $transformService;

    /**
     * @var AbstractSlave
     */
    private $abstractSlave;

    /**
     * @var ObjectProphecy|Module
     */
    private $slave;

    /**
     * @var ObjectProphecy|LogRepository
     */
    private $logRepository;

    protected function _before(): void
    {
        $this->masterService = $this->prophesize(MasterService::class);
        $this->transformService = $this->prophesize(TransformService::class);
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->slave = $this->prophesize(Module::class);

        $this->abstractSlave = new class($this->masterService->reveal(), $this->transformService->reveal(), $this->logRepository->reveal(), $this->slave->reveal()) extends AbstractSlave {
            /**
             * @var Module
             */
            private $slave;

            public function __construct(MasterService $masterService, TransformService $transformService, LogRepository $logRepository, Module $slave)
            {
                parent::__construct($masterService, $transformService, $logRepository);
                $this->slave = $slave;
            }

            public function handshake(Module $slave): Module
            {
                return $this->slave;
            }
        };
    }

    public function testWrite(): void
    {
        $master = $this->prophesize(Master::class);
        $this->slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($master->reveal())
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(2)
            ->willReturn(7)
        ;
        $this->masterService->send($master->reveal(), 255, '*Handtuch')
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReceiveReturn($master->reveal())
            ->shouldBeCalledOnce()
        ;

        $this->prophesizeAddLog($master, 255, 7, 42, 'Handtuch', 'output');

        $this->abstractSlave->write($this->slave->reveal(), 42, 'Handtuch');
    }

    public function testRead(): void
    {
        $master = $this->prophesize(Master::class);
        $this->slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($master->reveal())
        ;
        $this->slave->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn(7)
        ;
        $this->masterService->send($master->reveal(), 255, chr((7 << 1) | 1) . chr(42) . chr(8))
            ->shouldBeCalledOnce()
        ;
        $this->masterService->receiveReadData($master->reveal(), 7, 255, 42)
            ->shouldBeCalledOnce()
            ->willReturn('Handtuch')
        ;

        $this->prophesizeAddLog($master, 255, 7, 42, 'Handtuch', 'input');

        $this->assertEquals(
            'Handtuch',
            $this->abstractSlave->read($this->slave->reveal(), 42, 8)
        );
    }

    public function prophesizeAddLog(ObjectProphecy $master, int $type, int $slaveAddress, int $command, string $data, string $direction): void
    {
        $log = $this->prophesize(Log::class);
        $log->setMaster($master->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $log->setModule($this->slave->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $log->setSlaveAddress($slaveAddress)
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $log->setCommand($command)
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $log->save()
            ->shouldBeCalledOnce()
        ;
        $this->transformService->asciiToHex($data)
            ->shouldBeCalledOnce()
            ->willReturn('Unwarscheinlich')
        ;
        $this->logRepository->create($type, 'Unwarscheinlich', $direction)
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
    }
}
