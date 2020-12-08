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
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Repository\MasterRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use Psr\Log\LoggerInterface;

class ReceiverService extends AbstractService
{
    /**
     * @var TransformService
     */
    private $transformService;

    /**
     * @var MasterService
     */
    private $masterService;

    /**
     * @var MasterFormatter
     */
    private $masterFormatter;

    /**
     * @var MasterRepository
     */
    private $masterRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Server constructor.
     */
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
        $protocolService->sendReceiveReturn($busMessage);

        if ($busMessage->getType() === MasterService::TYPE_HANDSHAKE) {
            $this->handshake($protocolService, $busMessage);
        } else {
            $masterModel = $this->masterRepository->getByAddress($busMessage->getMasterAddress(), $protocolService->getName());
            $this->masterFormatter->extractSlaveDataFromMessage($busMessage);

            $this->masterService->receive($masterModel, $busMessage);
        }

        // Log schreiben
        // Push senden
        // Callbacks ausführen
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SaveError
     */
    private function handshake(ProtocolInterface $protocolService, BusMessage $busMessage): void
    {
        $protocolName = $protocolService->getName();
        $data = $busMessage->getData();

        if (empty($data)) {
            throw new GetError('No master name transmitted!');
        }

        try {
            $master = $this->masterRepository->getByName($data, $protocolName);
            $master
                ->setAddress($busMessage->getMasterAddress())
                ->save()
            ;
        } catch (SelectError $exception) {
            $master = $this->masterRepository->add($data, $protocolName, $busMessage->getMasterAddress());
        }

        $this->masterService->send(
            $master,
            (new BusMessage($master->getAddress(), MasterService::TYPE_HANDSHAKE))
                ->setData(
                    chr($master->getSendPort() >> 8) .
                    chr($master->getSendPort() & 255)
                )
                ->setPort(UdpService::START_PORT)
        );
    }
}
