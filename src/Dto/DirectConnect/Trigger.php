<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\DirectConnect;

use GibsonOS\Module\Hc\Dto\BusMessage;

class Trigger
{
    private BusMessage $busMessage;

    /**
     * @var int[]
     */
    private array $equalBytes = [];

    public function __construct(BusMessage $busMessage)
    {
        $this->busMessage = $busMessage;
    }

    public function getBusMessage(): BusMessage
    {
        return $this->busMessage;
    }

    public function setBusMessage(BusMessage $busMessage): Trigger
    {
        $this->busMessage = $busMessage;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getEqualBytes(): array
    {
        return $this->equalBytes;
    }

    /**
     * @param int[] $equalBytes
     */
    public function setEqualBytes(array $equalBytes): Trigger
    {
        $this->equalBytes = $equalBytes;

        return $this;
    }
}
