<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
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
        $data = $protocolService->receive();

        if (empty($data)) {
            return;
        }

        $this->masterFormatter->checksumEqual($data);

        $cleanData = $this->masterFormatter->getData($data);
        $masterAddress = $this->masterFormatter->getMasterAddress($data);
        $type = $this->masterFormatter->getType($data);

        $protocolService->sendReceiveReturn($masterAddress);

        if ($type === MasterService::TYPE_HANDSHAKE) {
            $this->handshake($protocolService, $cleanData, $masterAddress);
        } else {
            $masterModel = $this->masterRepository->getByAddress($masterAddress, $protocolService->getName());
            $this->masterService->receive($masterModel, $type, $cleanData);
        }

        // Log schreiben
        // Push senden
        // Callbacks ausführen
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws GetError
     * @throws SaveError
     * @throws FileNotFound
     */
    private function handshake(ProtocolInterface $protocolService, string $data, int $masterAddress): void
    {
        $protocolName = $protocolService->getName();

        try {
            $masterModel = $this->masterRepository->getByName($data, $protocolName);
        } catch (SelectError $exception) {
            $masterModel = $this->masterRepository->add($data, $protocolName);
        }

        $address = $masterModel->getAddress();
        $masterModel->setAddress($masterAddress);
        $this->masterService->setAddress($masterModel, $address);
    }
}
