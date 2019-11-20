<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Exception\Server\SendError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Factory\Master as MasterFactory;
use GibsonOS\Module\Hc\Model\Log as LogModel;
use GibsonOS\Module\Hc\Repository\Master as MasterRepository;
use GibsonOS\Module\Hc\Service\Protocol\AbstractProtocol;

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
     * Server constructor.
     *
     * @param AbstractProtocol $protocol
     * @param TransformService $transform
     */
    public function __construct(AbstractProtocol $protocol, TransformService $transform)
    {
        $this->protocol = $protocol;
        $this->transform = $transform;
    }

    /**
     * @throws AbstractException
     * @throws FileNotFound
     * @throws ReceiveError
     * @throws SaveError
     * @throws SelectError
     * @throws SendError
     */
    public function receive()
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
            $master = MasterFactory::createByAddress($masterAddress, $this->protocol->getName(), $this);
            $master->receive(
                $this->protocol->getType(),
                $this->protocol->getData()
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
    private function handshake()
    {
        try {
            $masterModel = MasterRepository::getByName($this->protocol->getData(), $this->protocol->getName());
        } catch (SelectError $exception) {
            $masterModel = MasterRepository::add($this->protocol->getData(), $this->protocol->getName());
        }

        $master = MasterFactory::create($masterModel, $this);
        $address = $master->getModel()->getAddress();
        $master->getModel()->setAddress($this->protocol->getMasterAddress());

        (new LogModel())
            ->setMasterId($master->getModel()->getId())
            ->setType(MasterService::TYPE_HANDSHAKE)
            ->setData($this->transform->asciiToHex($this->protocol->getData()))
            ->setDirection(self::DIRECTION_INPUT)
            ->save();

        $master->setAddress($address);
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
    public function receiveReadData($address, $type)
    {
        $this->protocol->receiveReadData();
        $this->protocol->checksumEqual();

        if ($this->protocol->getMasterAddress() != $address) {
            throw new ReceiveError('Master Adresse stimmt nicht überein!');
        }

        if ($this->protocol->getType() != $type) {
            throw new ReceiveError('Typ stimmt nicht überein!');
        }

        return $this->protocol->getData();
    }

    /**
     * @param int $address
     */
    public function receiveReceiveReturn($address)
    {
        $this->protocol->receiveReceiveReturn($address);
    }
}
