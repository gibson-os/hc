<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event\Describer;

use GibsonOS\Module\Hc\Dto\Event\Describer\Method;
use GibsonOS\Module\Hc\Dto\Event\Describer\Parameter\IntParameter;

class TimeService implements DescriberInterface
{
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'Zeit';
    }

    /**
     * Liste der Möglichen Events.
     *
     * @return array
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
        return [
            'sleep' => (new Method('Warten (s)'))
                ->setParameters([
                    'seconds' => (new IntParameter('Sekunden'))
                        ->setRange(1),
                ]),
            'usleep' => (new Method('Warten (ms)'))
                ->setParameters([
                    'microseconds' => (new IntParameter('Mikrosekunden'))
                        ->setRange(1),
                ]),
        ];
    }
}
