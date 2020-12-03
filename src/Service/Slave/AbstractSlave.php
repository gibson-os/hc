<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;

abstract class AbstractSlave extends AbstractService
{
    const READ_BIT = 1;

    const WRITE_BIT = 0;

    /**
     * @var MasterService
     */
    protected $masterService;

    /**
     * @var TransformService
     */
    protected $transformService;

    /**
     * @var LogRepository
     */
    private $logRepository;

    abstract public function handshake(Module $slave): Module;

    /**
     * Slave constructor.
     */
    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        LogRepository $logRepository
    ) {
        $this->masterService = $masterService;
        $this->transformService = $transformService;
        $this->logRepository = $logRepository;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function write(Module $slave, int $command, string $data): void
    {
        $this->masterService->send(
            $slave->getMaster(),
            (new BusMessage($slave->getMaster()->getAddress(), MasterService::TYPE_DATA))
                ->setSlaveAddress($slave->getAddress())
                ->setCommand($command)
                ->setWrite(true)
                ->setData($data)
        );
        $this->masterService->receiveReceiveReturn($slave->getMaster());
        $this->addLog($slave, MasterService::TYPE_DATA, $command, $data, Log::DIRECTION_OUTPUT);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function read(Module $slave, int $command, int $length): string
    {
        $busMessage = (new BusMessage($slave->getMaster()->getAddress(), MasterService::TYPE_DATA))
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->setData(chr($length))
        ;
        $this->masterService->send($slave->getMaster(), $busMessage);
        $receivedBusMessage = $this->masterService->receiveReadData($slave->getMaster(), $busMessage);
        $this->addLog(
            $slave,
            MasterService::TYPE_DATA,
            $command,
            $receivedBusMessage->getData() ?? '',
            Log::DIRECTION_INPUT
        );

        return $receivedBusMessage->getData() ?? '';
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    private function addLog(Module $slave, int $type, int $command, string $data, string $direction): void
    {
        $this->logRepository->create($type, $this->transformService->asciiToHex($data), $direction)
            ->setMaster($slave->getMaster())
            ->setModule($slave)
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->save();
    }
}
