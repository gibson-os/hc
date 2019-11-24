<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event\Describer;

use GibsonOS\Module\Hc\Dto\Event\Describer\Method;
use GibsonOS\Module\Hc\Dto\Event\Describer\Trigger;

class NeopixelService implements DescriberInterface
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
}
