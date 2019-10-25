<?php
namespace GibsonOS\Module\Hc\Service\Event\Describer;

use GibsonOS\Module\Hc\Config\Event\Describer\Method;
use GibsonOS\Module\Hc\Config\Event\Describer\Trigger;

class Neopixel implements DescriberInterface
{
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'Neopixel';
    }

    /**
     * Liste der Möglichen Events
     *
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        return [];
    }

    /**
     * Liste der Möglichen Kommandos
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return [];
    }
}