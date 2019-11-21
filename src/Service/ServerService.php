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
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Log as LogModel;
use GibsonOS\Module\Hc\Repository\Master as MasterRepository;
use GibsonOS\Module\Hc\Service\Protocol\AbstractProtocol;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;

class ServerService extends AbstractService
{
    const DIRECTION_INPUT = 'input';

    const DIRECTION_OUTPUT = 'output';

    /**
     * @var AbstractProtocol
     */
    private $protocol;

    /**
     * @var TransformService
     */
    private $transform;

    /**
     * @var MasterRepository
     */
    private $masterRepository;

    /**
     * Server constructor.
     *
     * @param AbstractProtocol $protocol
     * @param TransformService $transform
     * @param MasterRepository $masterRepository
     */
    public function __construct(
        AbstractProtocol $protocol,
        TransformService $transform,
        MasterRepository $masterRepository
    ) {
        $this->protocol = $protocol;
        $this->transform = $transform;
        $this->masterRepository = $masterRepository;
    }

    /**
     * @param MasterService   $master
     * @param AbstractHcSlave $slave
     *
     * @throws AbstractException
     * @throws DateTimeError
     * @throws FileNotFound
     * @throws GetError
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws SendError
     */
    public function receive(MasterService $master, AbstractHcSlave $slave): void
    {
        if (!$this->protocol->receive()) {
            return;
        }

        $this->protocol->checksumEqual();
        $this->protocol->sendReceiveReturn();
        $masterAddress = $this->protocol->getMasterAddress();

        if ($this->protocol->getType() === MasterService::TYPE_HANDSHAKE) {
            $this->handshake();
        } else {
            $masterModel = $this->masterRepository->getByAddress($masterAddress, $this->protocol->getName());
            $master->receive(
                $masterModel,
                $slave,
                $this->protocol->getType(),
                (string) $this->protocol->getData()
            );
        }

        // Log schreiben
        // Push senden
        // Callbacks ausführen
    }

    /**
     * @throws AbstractException
     * @throws FileNotFound
     * @throws SaveError
     */
    private function handshake(): void
    {
        try {
            $masterModel = $this->masterRepository->getByName((string) $this->protocol->getData(), $this->protocol->getName());
        } catch (SelectError $exception) {
            $masterModel = $this->masterRepository->add((string) $this->protocol->getData(), $this->protocol->getName());
        }

        $address = $masterModel->getAddress();
        $masterModel->setAddress($this->protocol->getMasterAddress());

        (new LogModel())
            ->setMasterId((int) $masterModel->getId())
            ->setType(MasterService::TYPE_HANDSHAKE)
            ->setData($this->transform->asciiToHex((string) $this->protocol->getData()))
            ->setDirection(self::DIRECTION_INPUT)
            ->save();

        $masterModel->setAddress($address);
    }

    /**
     * @param int    $address
     * @param int    $type
     * @param string $data
     *
     * @throws AbstractException
     */
    public function send($address, $type, $data)
    {
        $this->protocol->send($type, $data, $address);
        usleep(1);
    }

    /**
     * @param int $address
     * @param int $type
     *
     * @throws ReceiveError
     *
     * @return string
     */
    public function receiveReadData($address, $type): string
    {
        $this->protocol->receiveReadData();
        $this->protocol->checksumEqual();

        if ($this->protocol->getMasterAddress() != $address) {
            throw new ReceiveError('Master Adresse stimmt nicht überein!');
        }

        if ($this->protocol->getType() != $type) {
            throw new ReceiveError('Typ stimmt nicht überein!');
        }

        return (string) $this->protocol->getData();
    }

    /**
     * @param int $address
     */
    public function receiveReceiveReturn($address)
    {
        $this->protocol->receiveReceiveReturn($address);
    }
}
