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

class SenderService extends AbstractService
{
    /**
     * @var MasterFormatter
     */
    private $masterFormatter;

    /**
     * @var TransformService
     */
    private $transformService;

    /**
     * @var MasterRepository
     */
    private $masterRepository;

    /**
     * @var ProtocolFactory
     */
    private $protocolFactory;

    /**$data
     * Server constructor.
     */
    public function __construct(
        MasterFormatter $masterFormatter,
        TransformService $transformService,
        MasterRepository $masterRepository,
        ProtocolFactory $protocolFactory
    ) {
        $this->masterFormatter = $masterFormatter;
        $this->transformService = $transformService;
        $this->masterRepository = $masterRepository;
        $this->protocolFactory = $protocolFactory;
    }

    /**
     * @throws AbstractException
     */
    public function send(BusMessage $busMessage, string $protocol): void
    {
        $this->protocolFactory->get($protocol)->send($busMessage);
        usleep(500);
    }

    /**
     * @throws ReceiveError
     * @throws FactoryError
     */
    public function receiveReadData(Master $master, int $type): BusMessage
    {
        $protocolService = $this->protocolFactory->get($master->getProtocol());
        $busMessage = $protocolService->receiveReadData();

        $this->masterFormatter->checksumEqual($busMessage);

        if ($busMessage->getMasterAddress() !== $master->getAddress()) {
            throw new ReceiveError(sprintf(
                'Master Adresse %s not equal with data master %s!',
                $master->getAddress(),
                $busMessage->getMasterAddress()
            ));
        }

        if ($busMessage->getType() !== $type) {
            throw new ReceiveError(sprintf(
                'Type %d not equal with data type %d!',
                $type,
                $busMessage->getType()
            ));
        }

        return $busMessage;
    }

    /**
     * @throws FactoryError
     */
    public function receiveReceiveReturn(Master $master): void
    {
        $this->protocolFactory->get($master->getProtocol())->receiveReceiveReturn((string) $master->getAddress());
    }
}
