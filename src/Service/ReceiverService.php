<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

class ReceiverService
{
    public function __construct(
        private MasterService $masterService,
        private MasterMapper $masterMapper,
        private MasterRepository $masterRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws AbstractException
     * @throws GetError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function receive(ProtocolInterface $protocolService): void
    {
        $this->logger->debug('Receive data');
        $busMessage = $protocolService->receive();

        if ($busMessage === null || $busMessage->getData() === null || strlen($busMessage->getData() ?? '') === 0) {
            return;
        }

        $this->logger->debug(sprintf(
            'Received message "%s" from master %s',
            $busMessage->getData() ?? '',
            $busMessage->getMasterAddress()
        ));
        $this->masterMapper->checksumEqual($busMessage);

        if ($busMessage->getType() === MasterService::TYPE_HANDSHAKE) {
            $this->masterService->handshake($protocolService, $busMessage);
        } else {
            $masterModel = $this->masterRepository->getByAddress($busMessage->getMasterAddress(), $protocolService->getName());
            $this->masterMapper->extractSlaveDataFromMessage($busMessage);

            $this->masterService->receive($masterModel, $busMessage);
        }

        // Log schreiben
        // Push senden
        // Callbacks ausf??hren
    }
}
