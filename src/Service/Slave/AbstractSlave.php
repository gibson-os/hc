<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use Psr\Log\LoggerInterface;

abstract class AbstractSlave extends AbstractService
{
    protected const MAX_DATA_LENGTH = 32;

    abstract public function handshake(Module $slave): Module;

    public function __construct(protected MasterService $masterService, protected TransformService $transformService, private LogRepository $logRepository, protected LoggerInterface $logger)
    {
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    public function write(Module $slave, int $command, string $data): void
    {
        $master = $slave->getMaster();

        if ($master === null) {
            throw new WriteException(sprintf('Slave #%d has no master!', $slave->getId() ?? 0));
        }

        $this->logger->debug(sprintf(
            'Write command %d with data "%s" to %d',
            $command,
            $data,
            $slave->getAddress() ?? 0
        ));
        $busMessage = (new BusMessage($master->getAddress(), MasterService::TYPE_DATA))
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->setWrite(true)
            ->setData($data)
            ->setPort($master->getSendPort())
        ;
        $this->masterService->send($master, $busMessage);
        $this->masterService->receiveReceiveReturn($master, $busMessage);
        $this->addLog($slave, $command, $data, Log::DIRECTION_OUTPUT);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function read(Module $slave, int $command, int $length): string
    {
        $master = $slave->getMaster();

        if ($master === null) {
            throw new ReceiveError(sprintf('Slave #%d has no master!', $slave->getId() ?? 0));
        }

        $this->logger->debug(sprintf(
            'Read command %d with length %d from slave %d on master %s',
            $command,
            $length,
            $slave->getAddress() ?? 0,
            $master->getAddress()
        ));
        $busMessage = (new BusMessage($master->getAddress(), MasterService::TYPE_DATA))
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->setData(chr($length))
            ->setPort($master->getSendPort())
        ;
        $this->masterService->send($master, $busMessage);
        $receivedBusMessage = $this->masterService->receiveReadData($master, $busMessage);
        $this->logger->debug(sprintf(
            'Read data "%s" from slave %d on master %s with command %d',
            $receivedBusMessage->getData() ?? '',
            $receivedBusMessage->getSlaveAddress() ?? 0,
            $receivedBusMessage->getMasterAddress(),
            $receivedBusMessage->getCommand() ?? ''
        ));
        $this->addLog(
            $slave,
            $command,
            $receivedBusMessage->getData() ?? '',
            Log::DIRECTION_INPUT
        );

        return $receivedBusMessage->getData() ?? '';
    }

    /**
     * @throws SaveError
     */
    private function addLog(Module $slave, int $command, string $data, string $direction): void
    {
        $this->logRepository->create(MasterService::TYPE_DATA, $data, $direction)
            ->setMaster($slave->getMaster())
            ->setModule($slave)
            ->setSlaveAddress($slave->getAddress())
            ->setCommand($command)
            ->save()
        ;
    }
}
