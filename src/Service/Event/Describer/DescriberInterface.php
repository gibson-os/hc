<?php
namespace GibsonOS\Module\Hc\Service\Event\Describer;

use GibsonOS\Module\Hc\Config\Event\Describer\Method;
use GibsonOS\Module\Hc\Config\Event\Describer\Trigger;

interface DescriberInterface
{
    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * Liste der Möglichen Events
     *
     * @return Trigger[]
     */
    public function getTriggers(): array;

    /**
     * Liste der Möglichen Kommandos
     *
     * @return Method[]
     */
    public function getMethods(): array;
}