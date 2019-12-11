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
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Master as MasterRepository;
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

        $masterAddress = $this->formatter->getMasterAddress($data);
        $type = $this->formatter->getType($data);

        $protocol->sendReceiveReturn($masterAddress);

        if ($type === MasterService::TYPE_HANDSHAKE) {
            $this->handshake($protocol, $data);
        } else {
            $masterModel = $this->masterRepository->getByAddress($masterAddress, $protocol->getName());
            $this->master->receive($masterModel, $type, $this->formatter->getData($data));
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
    private function handshake(ProtocolInterface $protocol, string $data): void
    {
        try {
            $masterModel = $this->masterRepository->getByName($data, $protocol->getName());
        } catch (SelectError $exception) {
            $masterModel = $this->masterRepository->add($data, $protocol->getName());
        }

        $address = $masterModel->getAddress();
        $masterModel->setAddress($this->formatter->getMasterAddress($data));

        (new Log())
            ->setMasterId((int) $masterModel->getId())
            ->setType(MasterService::TYPE_HANDSHAKE)
            ->setData($this->transform->asciiToHex((string) $this->formatter->getData($data)))
            ->setDirection(Log::DIRECTION_INPUT)
            ->save();

        $masterModel->setAddress($address);
    }
}
