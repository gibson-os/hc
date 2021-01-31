<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use Psr\Log\LoggerInterface;

class ReceiverService extends AbstractService
{
    private TransformService $transformService;

    private MasterService $masterService;

    private MasterFormatter $masterFormatter;

    private MasterRepository $masterRepository;

    private LoggerInterface $logger;

    public function __construct(
        TransformService $transformService,
        MasterService $masterService,
        MasterFormatter $masterFormatter,
        MasterRepository $masterRepository,
        LoggerInterface $logger
    ) {
        $this->transformService = $transformService;
        $this->masterService = $masterService;
        $this->masterFormatter = $masterFormatter;
        $this->masterRepository = $masterRepository;
        $this->logger = $logger;
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     */
    public function receive(ProtocolInterface $protocolService): void
    {
        $this->logger->debug('Receive data');
        $busMessage = $protocolService->receive();

        if ($busMessage === null) {
            return;
        }

        $this->logger->debug(sprintf(
            'Received message "%s" from master %s',
            $busMessage->getData() ?? '',
            $busMessage->getMasterAddress()
        ));
        $this->masterFormatter->checksumEqual($busMessage);

        if ($busMessage->getType() === MasterService::TYPE_HANDSHAKE) {
            $this->masterService->handshake($protocolService, $busMessage);
        } else {
            $masterModel = $this->masterRepository->getByAddress($busMessage->getMasterAddress(), $protocolService->getName());
            $this->masterFormatter->extractSlaveDataFromMessage($busMessage);

            $this->masterService->receive($masterModel, $busMessage);
        }

        // Log schreiben
        // Push senden
        // Callbacks ausführen
    }
}
