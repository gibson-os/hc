<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\Describer;

use GibsonOS\Core\Dto\Event\Describer\Method;
use GibsonOS\Core\Dto\Event\Describer\Trigger;
use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\AutoComplete\Neopixel\ImageAutoComplete;
use GibsonOS\Module\Hc\AutoComplete\SlaveAutoComplete;
use GibsonOS\Module\Hc\Event\NeopixelEvent;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Repository\TypeRepository;

class NeopixelDescriber extends AbstractHcDescriber
{
    private ImageAutoComplete $imageAutoComplete;

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function __construct(
        TypeRepository $typeRepository,
        SlaveAutoComplete $slaveAutoComplete,
        ImageAutoComplete $imageAutoComplete
    ) {
        parent::__construct($typeRepository, $slaveAutoComplete);
        $this->slaveParameter->setSlaveType($this->typeRepository->getByHelperName('neopixel'));
        $this->imageAutoComplete = $imageAutoComplete;
    }

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
        $imageParameter = new AutoCompleteParameter('Bild', $this->imageAutoComplete);
        $imageParameter->setListener('slave', ['params' => [
            'paramKey' => 'moduleId',
            'recordKey' => 'id',
        ]]);

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
                    'imageId' => $imageParameter,
                ]),
            'sendAnimation' => (new Method('Animation senden'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                ]),
            'randomImage' => (new Method('Zufallsanzeige'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'start' => (new IntParameter('Start LED'))
                        ->setRange(1, LedMapper::MAX_PROTOCOL_LEDS + 1),
                    'end' => (new IntParameter('End LED'))
                        ->setRange(1, LedMapper::MAX_PROTOCOL_LEDS + 1),
                    'redFrom' => (new IntParameter('Rot von'))
                        ->setRange(0, 255),
                    'redTo' => (new IntParameter('Rot bis'))
                        ->setRange(0, 255),
                    'greenFrom' => (new IntParameter('Grün von'))
                        ->setRange(0, 255),
                    'greenTo' => (new IntParameter('Grün bis'))
                        ->setRange(0, 255),
                    'blueFrom' => (new IntParameter('Blau von'))
                        ->setRange(0, 255),
                    'blueTo' => (new IntParameter('Blau bis'))
                        ->setRange(0, 255),
                ]),
            'sendColor' => (new Method('Farbe setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'ledRanges' => new StringParameter('LEDs'),
                    'red' => (new IntParameter('Rot'))
                        ->setRange(0, 255),
                    'green' => (new IntParameter('Grün'))
                        ->setRange(0, 255),
                    'blue' => (new IntParameter('Blau'))
                        ->setRange(0, 255),
                ]),
        ]);
    }

    public function getEventClassName(): string
    {
        return NeopixelEvent::class;
    }
}
