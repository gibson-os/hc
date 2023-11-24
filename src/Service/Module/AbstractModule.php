<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Module;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use Psr\Log\LoggerInterface;
use ReflectionException;

abstract class AbstractModule
{
    protected const MAX_DATA_LENGTH = 32;

    abstract public function handshake(Module $module): Module;

    public function __construct(
        protected readonly MasterService $masterService,
        protected readonly TransformService $transformService,
        private readonly LogRepository $logRepository,
        protected readonly LoggerInterface $logger,
        protected readonly ModelManager $modelManager,
        protected readonly ModelWrapper $modelWrapper,
    ) {
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
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
        $this->addLog($slave, $command, $data, Direction::OUTPUT);
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     */
    public function read(Module $module, int $command, int $length): string
    {
        $master = $module->getMaster();

        if ($master === null) {
            throw new ReceiveError(sprintf('Slave #%d has no master!', $module->getId() ?? 0));
        }

        $this->logger->debug(sprintf(
            'Read command %d with length %d from slave %d on master %s',
            $command,
            $length,
            $module->getAddress() ?? 0,
            $master->getAddress()
        ));
        $busMessage = (new BusMessage($master->getAddress(), MasterService::TYPE_DATA))
            ->setSlaveAddress($module->getAddress())
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
            $module,
            $command,
            $receivedBusMessage->getData() ?? '',
            Direction::INPUT
        );

        return $receivedBusMessage->getData() ?? '';
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws ClientException
     * @throws RecordException
     */
    private function addLog(Module $slave, int $command, string $data, Direction $direction): void
    {
        $this->modelManager->save(
            $this->logRepository->create(MasterService::TYPE_DATA, $data, $direction)
                ->setMaster($slave->getMaster())
                ->setModule($slave)
                ->setSlaveAddress($slave->getAddress())
                ->setCommand($command)
        );
    }
}
