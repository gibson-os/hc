<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Factory\ProtocolFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository as MasterRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use Psr\Log\LoggerInterface;

class SenderService extends AbstractService
{
    private MasterFormatter $masterFormatter;

    private TransformService $transformService;

    private MasterRepository $masterRepository;

    private ProtocolFactory $protocolFactory;

    private LoggerInterface $logger;

    public function __construct(
        MasterFormatter $masterFormatter,
        TransformService $transformService,
        MasterRepository $masterRepository,
        ProtocolFactory $protocolFactory,
        LoggerInterface $logger
    ) {
        $this->masterFormatter = $masterFormatter;
        $this->transformService = $transformService;
        $this->masterRepository = $masterRepository;
        $this->protocolFactory = $protocolFactory;
        $this->logger = $logger;
    }

    /**
     * @throws AbstractException
     */
    public function send(BusMessage $busMessage, string $protocol): void
    {
        $this->protocolFactory->get($protocol)->send($busMessage);
    }

    /**
     * @throws FactoryError
     * @throws ReceiveError
     */
    public function receiveReadData(Master $master, int $type): BusMessage
    {
        $protocolService = $this->protocolFactory->get($master->getProtocol());
        $busMessage = $protocolService->receiveReadData($master->getSendPort());

        $this->masterFormatter->checksumEqual($busMessage);

        if ($busMessage->getMasterAddress() !== $master->getAddress()) {
            throw new ReceiveError(sprintf(
                'Master Adresse %s not equal with received master %s!',
                $master->getAddress(),
                $busMessage->getMasterAddress()
            ));
        }

        if ($busMessage->getType() !== $type) {
            throw new ReceiveError(sprintf(
                'Type %d not equal with received type %d!',
                $type,
                $busMessage->getType()
            ));
        }

        return $busMessage;
    }

    /**
     * @throws FactoryError
     */
    public function receiveReceiveReturn(Master $master, BusMessage $busMessage): void
    {
        $this->protocolFactory->get($master->getProtocol())->receiveReceiveReturn($busMessage);
    }
}
