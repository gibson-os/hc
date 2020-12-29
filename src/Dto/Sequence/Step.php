<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Sequence;

use GibsonOS\Module\Hc\Dto\BusMessage;

class Step
{
    private int $runtime;

    private BusMessage $busMessage;

    public function __construct(int $runtime, BusMessage $busMessage)
    {
        $this->runtime = $runtime;
        $this->busMessage = $busMessage;
    }

    public function getRuntime(): int
    {
        return $this->runtime;
    }

    public function setRuntime(int $runtime): Step
    {
        $this->runtime = $runtime;

        return $this;
    }

    public function getBusMessage(): BusMessage
    {
        return $this->busMessage;
    }

    public function setBusMessage(BusMessage $busMessage): Step
    {
        $this->busMessage = $busMessage;

        return $this;
    }
}
