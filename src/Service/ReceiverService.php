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
     * Server constructor.
     */
    public function __construct(
        TransformService $transformService,
        MasterService $masterService,
        MasterFormatter $masterFormatter,
        MasterRepository $masterRepository
    ) {
        $this->transformService = $transformService;
        $this->masterService = $masterService;
        $this->masterFormatter = $masterFormatter;
        $this->masterRepository = $masterRepository;
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
        $busMessage = $protocolService->receive();

        if ($busMessage === null) {
            return;
        }

        $this->masterFormatter->checksumEqual($busMessage);
        $protocolService->sendReceiveReturn($busMessage->getMasterAddress());

        if ($busMessage->getType() === MasterService::TYPE_HANDSHAKE) {
            $this->handshake($protocolService, $busMessage);
        } else {
            $masterModel = $this->masterRepository->getByAddress($busMessage->getMasterAddress(), $protocolService->getName());
            $data = $busMessage->getData();

            if (empty($data)) {
                throw new ReceiveError('No slave address in data!');
            }

            $busMessage
                ->setSlaveAddress($this->transformService->asciiToUnsignedInt($data, 0))
                ->setData(substr($data, 1));

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
            $masterModel = $this->masterRepository->getByName($data, $protocolName);
            $masterModel->setAddress($busMessage->getMasterAddress());
        } catch (SelectError $exception) {
            $this->masterRepository->add($data, $protocolName, $busMessage->getMasterAddress());
        }
    }
}
