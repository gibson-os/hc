<?php
declare(strict_types=1);

namespace Gibson\Test\Unit\Service\Slave;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;
use GibsonOS\Module\Hc\Service\TransformService;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class AbstractSlaveTest extends Unit
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|MasterService
     */
    private $masterService;

    /**
     * @var TransformService
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
        $this->transformService = new TransformService();
        $this->logRepository = $this->prophesize(LogRepository::class);
        $this->slave = $this->prophesize(Module::class);

        $this->abstractSlave = new class($this->masterService->reveal(), $this->transformService, $this->logRepository->reveal(), $this->slave->reveal()) extends AbstractSlave {
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
        self::prophesizeWrite(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            7,
            42,
            'Handtuch'
        );

        $this->abstractSlave->write($this->slave->reveal(), 42, 'Handtuch');
    }

    public function testRead(): void
    {
        self::prophesizeRead(
            $this->prophesize(Master::class),
            $this->slave,
            $this->masterService,
            $this->transformService,
            $this->logRepository,
            $this->prophesize(Log::class),
            255,
            7,
            42,
            'Handtuch',
            8
        );

        $this->assertEquals(
            'Handtuch',
            $this->abstractSlave->read($this->slave->reveal(), 42, 8)
        );
    }

    public static function prophesizeWrite(
        ObjectProphecy $master,
        ObjectProphecy $slave,
        ObjectProphecy $masterService,
        TransformService $transformService,
        ObjectProphecy $logRepository,
        ObjectProphecy $log,
        int $type,
        int $slaveAddress,
        int $command,
        string $data
    ): void {
        $slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($master->reveal())
        ;
        $slave->getAddress()
            ->shouldBeCalledTimes(2)
            ->willReturn($slaveAddress)
        ;
        $masterService->send($master->reveal(), $type, chr($slaveAddress << 1) . chr($command) . $data)
            ->shouldBeCalledOnce()
        ;
        $masterService->receiveReceiveReturn($master->reveal())
            ->shouldBeCalledOnce()
        ;

        self::prophesizeAddLog(
            $master,
            $slave,
            $transformService,
            $logRepository,
            $log,
            $type,
            $slaveAddress,
            $command,
            $data,
            'output'
        );
    }

    public static function prophesizeRead(
        ObjectProphecy $master,
        ObjectProphecy $slave,
        ObjectProphecy $masterService,
        TransformService $transformService,
        ObjectProphecy $logRepository,
        ObjectProphecy $log,
        int $type,
        int $slaveAddress,
        int $command,
        string $data,
        int $dataLength
    ): void {
        $slave->getMaster()
            ->shouldBeCalledTimes(3)
            ->willReturn($master->reveal())
        ;
        $slave->getAddress()
            ->shouldBeCalledTimes(3)
            ->willReturn($slaveAddress)
        ;
        $masterService->send($master->reveal(), $type, chr(($slaveAddress << 1) | 1) . chr($command) . chr($dataLength))
            ->shouldBeCalledOnce()
        ;
        $masterService->receiveReadData($master->reveal(), $slaveAddress, $type, $command)
            ->shouldBeCalledOnce()
            ->willReturn((new BusMessage('42.42.42.42', $type))->setData($data))
        ;

        self::prophesizeAddLog(
            $master,
            $slave,
            $transformService,
            $logRepository,
            $log,
            $type,
            $slaveAddress,
            $command,
            $data,
            'input'
        );
    }

    public static function prophesizeAddLog(
        ObjectProphecy $master,
        ObjectProphecy $slave,
        TransformService $transformService,
        ObjectProphecy $logRepository,
        ObjectProphecy $log,
        int $type,
        int $slaveAddress,
        int $command,
        string $data,
        string $direction
    ): void {
        $log->setMaster($master->reveal())
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
        $log->setModule($slave->reveal())
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
        $logRepository->create($type, $data, $direction)
            ->shouldBeCalledOnce()
            ->willReturn($log->reveal())
        ;
    }
}
