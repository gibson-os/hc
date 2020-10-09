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
        return array_merge(parent::getTriggers(), [
        ]);
    }

    /**
     * Liste der Möglichen Kommandos.
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return array_merge(parent::getMethods(), [
            'writeSetLeds' => (new Method('Leds setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeChannel' => (new Method('Channel schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeSequenceStart' => (new Method('Sequenz starten'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeSequenceStop' => (new Method('Sequenz stoppen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeSequencePause' => (new Method('Sequenz pausieren'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeSequenceEepromAddress' => (new Method('Sequenz EEPROM Adresse schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'readSequenceEepromAddress' => (new Method('Sequenz EEPROM Adresse lesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeSequenceNew' => (new Method('Neue Sequenz übertragen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeSequenceAddStep' => (new Method('Sequenz Schritt hinzufügen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'readLedCounts' => (new Method('LED Anzahl lesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'writeLedCounts' => (new Method('LED Anzahl schreiben'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'sendImage' => (new Method('Bild senden'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'sendAnimation' => (new Method('Bild senden'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
        ]);
    }

    public function getEventClassName(): string
    {
        return NeopixelEvent::class;
    }
}
