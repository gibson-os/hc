<?php
namespace GibsonOS\Module\Hc\Service\Event\Describer;

use GibsonOS\Module\Hc\Config\Event\Describer\Method;
use GibsonOS\Module\Hc\Config\Event\Describer\Parameter\IntParameter;

class Time implements DescriberInterface
{
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'Zeit';
    }

    /**
     * Liste der Möglichen Events
     *
     * @return array
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
        return [
            'sleep' => (new Method('Warten (s)'))
                ->setParameters([
                    'seconds' => (new IntParameter('Sekunden'))
                        ->setRange(1)
                ]),
            'usleep' => (new Method('Warten (ms)'))
                ->setParameters([
                    'microseconds' => (new IntParameter('Mikrosekunden'))
                        ->setRange(1)
                ])
        ];
    }
}