<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;

abstract class AbstractSlave extends AbstractService
{
    protected MasterService $masterService;

    protected TransformService $transformService;

    private LogRepository $logRepository;

    protected LoggerInterface $logger;

    abstract public function handshake(Module $slave): Module;

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        LogRepository $logRepository,
        LoggerInterface $logger
    ) {
        $this->masterService = $masterService;
        $this->transformService = $transformService;
        $this->logRepository = $logRepository;
        $this->logger = $logger;
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function write(Module $slave, int $command, string $data): void
    {
        $this->logger->debug(sprintf(
            'Write command %d with data "%s" to %d',
            $command,
            $data,
            $slave->getAddress() ?? 0
        ));
        $busMessage = (new BusMessage($slave->getMaster()->getAddress(), MasterService::TYPE_DATA))
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->setWrite(true)
            ->setData($data)
            ->setPort($slave->getMaster()->getSendPort())
        ;
        $this->masterService->send($slave->getMaster(), $busMessage);
        $this->masterService->receiveReceiveReturn($slave->getMaster(), $busMessage);
        $this->addLog($slave, MasterService::TYPE_DATA, $command, $data, Log::DIRECTION_OUTPUT);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function read(Module $slave, int $command, int $length): string
    {
        $this->logger->debug(sprintf(
            'Read command %d with length %d from slave %d on master %s',
            $command,
            $length,
            $slave->getAddress() ?? 0,
            $slave->getMaster()->getAddress()
        ));
        $busMessage = (new BusMessage($slave->getMaster()->getAddress(), MasterService::TYPE_DATA))
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->setData(chr($length))
            ->setPort($slave->getMaster()->getSendPort())
        ;
        $this->masterService->send($slave->getMaster(), $busMessage);
        $receivedBusMessage = $this->masterService->receiveReadData($slave->getMaster(), $busMessage);
        $this->logger->debug(sprintf(
            'Read data "%s" from slave %d on master %s with command %d',
            $receivedBusMessage->getData() ?? '',
            $receivedBusMessage->getSlaveAddress() ?? 0,
            $receivedBusMessage->getMasterAddress(),
            $receivedBusMessage->getCommand() ?? ''
        ));
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
