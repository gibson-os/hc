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
    private $transform;

    /**
     * @var MasterService
     */
    private $master;

    /**
     * @var MasterFormatter
     */
    private $formatter;

    /**
     * @var MasterRepository
     */
    private $masterRepository;

    /**
     * Server constructor.
     */
    public function __construct(
        TransformService $transform,
        MasterService $master,
        MasterFormatter $formatter,
        MasterRepository $masterRepository
    ) {
        $this->transform = $transform;
        $this->master = $master;
        $this->formatter = $formatter;
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
    public function receive(ProtocolInterface $protocol): void
    {
        $data = $protocol->receive();

        if (empty($data)) {
            return;
        }

        $this->formatter->checksumEqual($data);

        $cleanData = $this->formatter->getData($data);
        $masterAddress = $this->formatter->getMasterAddress($data);
        $type = $this->formatter->getType($data);

        $protocol->sendReceiveReturn($masterAddress);

        if ($type === MasterService::TYPE_HANDSHAKE) {
            $this->handshake($protocol, $cleanData, $masterAddress);
        } else {
            $masterModel = $this->masterRepository->getByAddress($masterAddress, $protocol->getName());
            $this->master->receive($masterModel, $type, $cleanData);
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
    private function handshake(ProtocolInterface $protocol, string $data, int $masterAddress): void
    {
        $protocolName = $protocol->getName();

        try {
            $masterModel = $this->masterRepository->getByName($data, $protocolName);
        } catch (SelectError $exception) {
            $masterModel = $this->masterRepository->add($data, $protocolName);
        }

        $address = $masterModel->getAddress();
        $masterModel->setAddress($masterAddress);
        $this->master->setAddress($masterModel, $address);
    }
}
