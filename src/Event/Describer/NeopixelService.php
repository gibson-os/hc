<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\Describer;

use GibsonOS\Core\Dto\Event\Describer\Method;
use GibsonOS\Core\Dto\Event\Describer\Trigger;
use GibsonOS\Core\Event\Describer\DescriberInterface;

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
