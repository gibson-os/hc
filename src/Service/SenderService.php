<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Factory\ProtocolFactory;
use GibsonOS\Module\Hc\Mapper\MasterMapper;
use GibsonOS\Module\Hc\Model\Master;

class SenderService
{
    public function __construct(private MasterMapper $masterMapper, private ProtocolFactory $protocolFactory)
    {
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

        $this->masterMapper->checksumEqual($busMessage);

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
