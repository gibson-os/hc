<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\Describer;

use GibsonOS\Core\Dto\Event\Describer\Method;
use GibsonOS\Core\Dto\Event\Describer\Trigger;
use GibsonOS\Module\Hc\Event\NeopixelEvent;

class NeopixelDescriber extends AbstractHcDescriber
{
    public function getTitle(): string
    {
        return 'Neopixel';
    }

    /**
     * Liste der Möglichen Events.
     *
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        return [];
    }

    /**
     * Liste der Möglichen Kommandos.
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return [];
    }

    public function getEventClassName(): string
    {
        return NeopixelEvent::class;
    }
}
