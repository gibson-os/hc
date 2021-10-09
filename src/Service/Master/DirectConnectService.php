<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Master;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\DirectConnect;
use GibsonOS\Module\Hc\Mapper\BusMessageMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\MasterService;

class DirectConnectService extends AbstractService
{
    private const TYPE_SEQUENCE_NEW = 100;

    private const TYPE_SEQUENCE_ADD_STEP = 101;

    private const TYPE_SEQUENCE_ADD_TRIGGER = 102;

    private const TYPE_SEQUENCE_START = 110;

    private const TYPE_SEQUENCE_STOP = 111;

    private const TYPE_SEQUENCE_PAUSE = 112;

    public function __construct(private MasterService $masterService, private BusMessageMapper $busMessageMapper)
    {
    }

    /**
     * @throws AbstractException
     */
    public function send(Master $master, DirectConnect $directConnect): void
    {
        $this->masterService->send($master, new BusMessage($master->getAddress(), self::TYPE_SEQUENCE_NEW));

        foreach ($directConnect->getSteps() as $step) {
            $this->addStep($master, $directConnect->getId(), $step);
        }

        foreach ($directConnect->getTriggers() as $trigger) {
            $this->addTrigger($master, $directConnect->getId(), $trigger);
        }
    }

    /**
     * @throws AbstractException
     */
    public function addStep(Master $master, int $id, DirectConnect\Step $step): void
    {
        $this->masterService->send(
            $master,
            (new BusMessage($master->getAddress(), self::TYPE_SEQUENCE_ADD_STEP))->setData(
                chr($id) .
                chr($step->getRuntime() >> 8) .
                chr($step->getRuntime() & 255) .
                $this->busMessageMapper->mapSlaveData($step->getBusMessage())
            )
        );
    }

    /**
     * @throws AbstractException
     */
    public function addTrigger(Master $master, int $id, DirectConnect\Trigger $trigger): void
    {
        $busMessage = $trigger->getBusMessage();
        $data = $busMessage->getData() ?? '';
        $newData = '';

        foreach ($trigger->getEqualBytes() as $equalByte) {
            $newData .= chr($equalByte) . (substr($data, $equalByte, 1) ?: '');
        }

        if (empty($newData)) {
            $newData = chr(255) . $data;
        }

        $busMessage->setData($newData);

        $this->masterService->send(
            $master,
            (new BusMessage($master->getAddress(), self::TYPE_SEQUENCE_ADD_TRIGGER))->setData(
                chr($id) .
                $this->busMessageMapper->mapSlaveData($busMessage)
            )
        );
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     */
    public function start(Module $slave): void
    {
        $this->masterService->send(
            $slave->getMaster(),
            (new BusMessage($slave->getMaster()->getAddress(), self::TYPE_SEQUENCE_START))
                ->setData(chr($slave->getAddress() ?? 0))
        );
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     */
    public function stop(Module $slave): void
    {
        $this->masterService->send(
            $slave->getMaster(),
            (new BusMessage($slave->getMaster()->getAddress(), self::TYPE_SEQUENCE_STOP))
                ->setData(chr($slave->getAddress() ?? 0))
        );
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     */
    public function pause(Module $slave): void
    {
        $this->masterService->send(
            $slave->getMaster(),
            (new BusMessage($slave->getMaster()->getAddress(), self::TYPE_SEQUENCE_PAUSE))
                ->setData(chr($slave->getAddress() ?? 0))
        );
    }
}
