<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Master;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Dto\BusMessage;
use GibsonOS\Module\Hc\Dto\DirectConnect\Step;
use GibsonOS\Module\Hc\Mapper\BusMessageMapper;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\MasterService;

class DirectConnectService extends AbstractService
{
    private const TYPE_SEQUENCE_NEW = 100;

    private const TYPE_SEQUENCE_ADD_STEP = 101;

    private const TYPE_SEQUENCE_START = 102;

    private const TYPE_SEQUENCE_STOP = 103;

    private const TYPE_SEQUENCE_PAUSE = 104;

    private MasterService $masterService;

    private BusMessageMapper $busMessageMapper;

    public function __construct(MasterService $masterService, BusMessageMapper $busMessageMapper)
    {
        $this->masterService = $masterService;
        $this->busMessageMapper = $busMessageMapper;
    }

    /**
     * @param Step[] $steps
     *
     * @throws AbstractException
     */
    public function send(Master $master, array $steps): void
    {
        $this->masterService->send($master, new BusMessage($master->getAddress(), self::TYPE_SEQUENCE_NEW));

        foreach ($steps as $step) {
            $this->masterService->send(
                $master,
                (new BusMessage($master->getAddress(), self::TYPE_SEQUENCE_ADD_STEP))->setData(
                    chr($step->getRuntime() >> 8) .
                    chr($step->getRuntime() & 255) .
                    $this->busMessageMapper->mapSlaveData($step->getBusMessage())
                )
            );
        }
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
