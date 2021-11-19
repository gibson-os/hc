<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\Describer;

use GibsonOS\Core\Dto\Event\Describer\Method;
use GibsonOS\Core\Dto\Event\Describer\Trigger;
use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Core\Dto\Parameter\BoolParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\OptionParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\AutoComplete\Io\PortAutoComplete;
use GibsonOS\Module\Hc\AutoComplete\SlaveAutoComplete;
use GibsonOS\Module\Hc\Event\IoEvent;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoSlave;

class IoDescriber extends AbstractHcDescriber
{
    public const BEFORE_READ_PORT = 'beforeReadPort';

    public const AFTER_READ_PORT = 'afterReadPort';

    public const BEFORE_WRITE_PORT = 'beforeWritePort';

    public const AFTER_WRITE_PORT = 'afterWritePort';

    public const BEFORE_READ_PORTS_FROM_EEPROM = 'beforeReadPortsFromEeprom';

    public const AFTER_READ_PORTS_FROM_EEPROM = 'afterReadPortsFromEeprom';

    public const BEFORE_WRITE_PORTS_TO_EEPROM = 'beforeWritePortsToEeprom';

    public const AFTER_WRITE_PORTS_TO_EEPROM = 'afterWritePortsToEeprom';

    public const BEFORE_READ_PORTS = 'beforeReadPorts';

    public const AFTER_READ_PORTS = 'afterReadPorts';

    public const BEFORE_ADD_DIRECT_CONNECT = 'beforeAddDirectConnect';

    public const AFTER_ADD_DIRECT_CONNECT = 'afterAddDirectConnect';

    public const BEFORE_SET_DIRECT_CONNECT = 'beforeSetDirectConnect';

    public const AFTER_SET_DIRECT_CONNECT = 'afterSetDirectConnect';

    public const BEFORE_SAVE_DIRECT_CONNECT = 'beforeSaveDirectConnect';

    public const AFTER_SAVE_DIRECT_CONNECT = 'afterSaveDirectConnect';

    public const BEFORE_READ_DIRECT_CONNECT = 'beforeReadDirectConnect';

    public const AFTER_READ_DIRECT_CONNECT = 'afterReadDirectConnect';

    public const BEFORE_DELETE_DIRECT_CONNECT = 'beforeDeleteDirectConnect';

    public const AFTER_DELETE_DIRECT_CONNECT = 'afterDeleteDirectConnect';

    public const BEFORE_RESET_DIRECT_CONNECT = 'beforeResetDirectConnect';

    public const AFTER_RESET_DIRECT_CONNECT = 'afterResetDirectConnect';

    public const BEFORE_DEFRAGMENT_DIRECT_CONNECT = 'beforeDefragmentDirectConnect';

    public const AFTER_DEFRAGMENT_DIRECT_CONNECT = 'afterDefragmentDirectConnect';

    public const BEFORE_ACTIVATE_DIRECT_CONNECT = 'beforeActivateDirectConnect';

    public const AFTER_ACTIVATE_DIRECT_CONNECT = 'afterActivateDirectConnect';

    public const BEFORE_IS_DIRECT_CONNECT_ACTIVE = 'beforeIsDirectConnectActive';

    public const AFTER_IS_DIRECT_CONNECT_ACTIVE = 'afterIsDirectConnectActive';

    private OptionParameter $directionParameter;

    private AutoCompleteParameter $portParameter;

    /**
     * @throws SelectError
     */
    public function __construct(
        TypeRepository $typeRepository,
        SlaveAutoComplete $slaveAutoComplete,
        PortAutoComplete $portAutoComplete
    ) {
        parent::__construct($typeRepository, $slaveAutoComplete);
        $this->slaveParameter->setSlaveType($this->typeRepository->getByHelperName('io'));
        $this->directionParameter = new OptionParameter('Richtung', [
            IoSlave::DIRECTION_INPUT => 'Eingang',
            IoSlave::DIRECTION_OUTPUT => 'Ausgang',
        ]);
        $this->portParameter = new AutoCompleteParameter('Port', $portAutoComplete);
        $this->portParameter->setListener('slave', ['params' => [
            'paramKey' => 'moduleId',
            'recordKey' => 'id',
        ]]);
    }

    public function getTitle(): string
    {
        return 'I/O';
    }

    /**
     * Liste der Möglichen Events.
     *
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        $portAttributeParameters = [
            'slave' => $this->slaveParameter,
            'number' => $this->portParameter,
            IoSlave::ATTRIBUTE_PORT_KEY_DIRECTION => $this->directionParameter,
            IoSlave::ATTRIBUTE_PORT_KEY_VALUE => new BoolParameter('An'),
            IoSlave::ATTRIBUTE_PORT_KEY_DELAY => new IntParameter('Verzögerung'),
            IoSlave::ATTRIBUTE_PORT_KEY_PULL_UP => new IntParameter('PullUo'),
            IoSlave::ATTRIBUTE_PORT_KEY_PWM => new IntParameter('PWM'),
            IoSlave::ATTRIBUTE_PORT_KEY_FADE_IN => new IntParameter('Einblenden'),
            IoSlave::ATTRIBUTE_PORT_KEY_BLINK => new IntParameter('Blinken'),
        ];
        $directConnectAttributeParameters = [
            'slave' => $this->slaveParameter,
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE => new BoolParameter('Eingangsport Geschloßen'),
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT => new IntParameter('Ausgangsport'),
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE => new BoolParameter('Ausgangsport An'),
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM => new IntParameter('Ausgangsport PWM'),
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK => new IntParameter('Ausgangsport Blinken'),
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN => new IntParameter('Ausgangsport Einblenden'),
            IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB => new IntParameter('Addieren oder subtrahieren'),
            'hasMore' => new BoolParameter('Es gibt weitere DirectConnects'),
        ];

        return array_merge(parent::getTriggers(), [
            self::BEFORE_READ_PORT => (new Trigger('Vor auslesen eines Ports'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'number' => $this->portParameter,
                ]),
            self::AFTER_READ_PORT => (new Trigger('Nach auslesen eines Ports'))
                ->setParameters($portAttributeParameters),
            self::BEFORE_WRITE_PORT => (new Trigger('Vor schreiben eines Ports'))
                ->setParameters($portAttributeParameters),
            self::AFTER_WRITE_PORT => (new Trigger('Nach schreiben eines Ports'))
                ->setParameters($portAttributeParameters),
            self::BEFORE_READ_PORTS_FROM_EEPROM => (new Trigger('Vor lesen der Ports aus EEPROM'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_READ_PORTS_FROM_EEPROM => (new Trigger('Nach lesen der Ports aus EEPROM'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::BEFORE_WRITE_PORTS_TO_EEPROM => (new Trigger('Vor schreiben der Ports in EEPROM'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_WRITE_PORTS_TO_EEPROM => (new Trigger('Nach schreiben der Ports in EEPROM'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::BEFORE_READ_PORTS => (new Trigger('Vor lesen der Ports'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_READ_PORTS => (new Trigger('Nach lesen der Ports'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::BEFORE_SAVE_DIRECT_CONNECT => (new Trigger('Vor speichern eines DirectConnects'))
                ->setParameters($directConnectAttributeParameters),
            self::AFTER_SAVE_DIRECT_CONNECT => (new Trigger('Nach speichern eines DirectConnects'))
                ->setParameters($directConnectAttributeParameters),
            self::BEFORE_ADD_DIRECT_CONNECT => (new Trigger('Vor hinzufügen eines DirectConnects'))
                ->setParameters($directConnectAttributeParameters),
            self::AFTER_ADD_DIRECT_CONNECT => (new Trigger('Nach hinzufügen eines DirectConnects'))
                ->setParameters($directConnectAttributeParameters),
            self::BEFORE_SET_DIRECT_CONNECT => (new Trigger('Vor überschreiben eines DirectConnects'))
                ->setParameters($directConnectAttributeParameters),
            self::AFTER_SET_DIRECT_CONNECT => (new Trigger('Nach überschreiben eines DirectConnects'))
                ->setParameters($directConnectAttributeParameters),
            self::BEFORE_READ_DIRECT_CONNECT => (new Trigger('Vor lesen eines DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'order' => new IntParameter('Position'),
                ]),
            self::AFTER_READ_DIRECT_CONNECT => (new Trigger('Nach lesen eines DirectConnects'))
                ->setParameters(array_merge($directConnectAttributeParameters, [
                    'port' => $this->portParameter,
                    'order' => new IntParameter('Position'),
                ])),
            self::BEFORE_DELETE_DIRECT_CONNECT => (new Trigger('Vor löschen eines DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'order' => new IntParameter('Position'),
                ]),
            self::AFTER_DELETE_DIRECT_CONNECT => (new Trigger('Nach löschen eines DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'order' => new IntParameter('Position'),
                ]),
            self::BEFORE_RESET_DIRECT_CONNECT => (new Trigger('Vor löschen aller DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'databaseOnly' => new BoolParameter('Nur Datenbank'),
                ]),
            self::AFTER_RESET_DIRECT_CONNECT => (new Trigger('Nach löschen aller DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'databaseOnly' => new BoolParameter('Nur Datenbank'),
                ]),
            self::BEFORE_DEFRAGMENT_DIRECT_CONNECT => (new Trigger('Vor defragmentieren der DirectConnects'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_DEFRAGMENT_DIRECT_CONNECT => (new Trigger('Nach defragmentieren der DirectConnects'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::BEFORE_ACTIVATE_DIRECT_CONNECT => (new Trigger('Vor de-/aktivieren der DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'active' => new BoolParameter('Aktiv'),
                ]),
            self::AFTER_ACTIVATE_DIRECT_CONNECT => (new Trigger('Nach de-/aktivieren der DirectConnects'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'active' => new BoolParameter('Aktiv'),
                ]),
            self::BEFORE_IS_DIRECT_CONNECT_ACTIVE => (new Trigger('Vor prüfen ob DirectConnects aktiv'))
                ->setParameters(['slave' => $this->slaveParameter]),
            self::AFTER_IS_DIRECT_CONNECT_ACTIVE => (new Trigger('Nach prüfen ob DirectConnects aktiv'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'active' => new BoolParameter('Aktiv'),
                ]),
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
            'readPort' => (new Method('Port lesen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'number' => $this->portParameter,
                ])
                ->setReturns([
                    IoSlave::ATTRIBUTE_PORT_KEY_DIRECTION => $this->directionParameter,
                    IoSlave::ATTRIBUTE_PORT_KEY_VALUE => new IntParameter('Wert'),
                    IoSlave::ATTRIBUTE_PORT_KEY_DELAY => new IntParameter('Verzögerung'),
                    IoSlave::ATTRIBUTE_PORT_KEY_PULL_UP => new IntParameter('PullUp'),
                    IoSlave::ATTRIBUTE_PORT_KEY_PWM => new IntParameter('PWM'),
                    IoSlave::ATTRIBUTE_PORT_KEY_FADE_IN => new IntParameter('Fade In'),
                    IoSlave::ATTRIBUTE_PORT_KEY_BLINK => new IntParameter('Blinken'),
                ]),
            'readPortsFromEeprom' => (new Method('Ports aus EEPROM lesen'))
                ->setParameters(['slave' => $this->slaveParameter]),
            'getPorts' => (new Method('Ports auslesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturns([
                    IoSlave::ATTRIBUTE_PORT_KEY_DIRECTION => $this->directionParameter,
                    IoSlave::ATTRIBUTE_PORT_KEY_VALUE => new IntParameter('Wert'),
                    IoSlave::ATTRIBUTE_PORT_KEY_DELAY => new IntParameter('Verzögerung'),
                    IoSlave::ATTRIBUTE_PORT_KEY_PULL_UP => new IntParameter('PullUp'),
                    IoSlave::ATTRIBUTE_PORT_KEY_PWM => new IntParameter('PWM'),
                    IoSlave::ATTRIBUTE_PORT_KEY_FADE_IN => new IntParameter('Fade In'),
                    IoSlave::ATTRIBUTE_PORT_KEY_BLINK => new IntParameter('Blinken'),
                ]),
            'readDirectConnect' => (new Method('DirectConnect lesen'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturns([
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE => new BoolParameter('Eingangsport Geschloßen'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT => new IntParameter('Ausgangsport'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE => new BoolParameter('Ausgangsport An'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM => new IntParameter('Ausgangsport PWM'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK => new IntParameter('Ausgangsport Blinken'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN => new IntParameter('Ausgangsport Einblenden'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB => new IntParameter('Addieren oder subtrahieren'),
                    'hasMore' => new BoolParameter('Es gibt weitere DirectConnects'),
                ]),
            'isDirectConnectActive' => (new Method('Ist DirectConnect aktiv'))
                ->setParameters(['slave' => $this->slaveParameter])
                ->setReturns(['value' => new BoolParameter('Aktiv')]),
            'setPort' => (new Method('Port setzen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'number' => $this->portParameter,
                    IoSlave::ATTRIBUTE_PORT_KEY_NAME => new StringParameter('Name'),
                    IoSlave::ATTRIBUTE_PORT_KEY_DIRECTION => $this->directionParameter,
                    IoSlave::ATTRIBUTE_PORT_KEY_PULL_UP => new IntParameter('PullUp'),
                    IoSlave::ATTRIBUTE_PORT_KEY_DELAY => new IntParameter('Verzögerung'),
                    IoSlave::ATTRIBUTE_PORT_KEY_PWM => new IntParameter('PWM'),
                    IoSlave::ATTRIBUTE_PORT_KEY_BLINK => new IntParameter('Blinken'),
                    IoSlave::ATTRIBUTE_PORT_KEY_FADE_IN => new IntParameter('Fade In'),
                    IoSlave::ATTRIBUTE_PORT_KEY_VALUE => new IntParameter('Werte Namen'),
                ]),
            'writePortsToEeprom' => new Method('Ports in EEPROM schreiben'),
            'saveDirectConnect' => (new Method('DirectConnect speichern'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'inputPort' => new IntParameter('Eingangsport'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE => new BoolParameter('Eingangsport geschloßen'),
                    'order' => new IntParameter('Reihenfolge'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT => new IntParameter('Ausgangsport'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE => new IntParameter('Ausgangsport An'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM => new IntParameter('Ausgangsport PWM'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK => new IntParameter('Ausgangsport Blinken'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN => new IntParameter('Ausgangsport Einblenden'),
                    IoSlave::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB => new IntParameter('Addieren oder subtrahieren'),
                ]),
            'deleteDirectConnect' => (new Method('DirectConnect löschen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'order' => new IntParameter('Reihenfolge'),
                ]),
            'resetDirectConnect' => (new Method('Alle DirectConnects löschen'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'port' => $this->portParameter,
                    'databaseOnly' => new BoolParameter('Nur Datenbank'),
                ]),
            'defragmentDirectConnect' => (new Method('DirectConnect defragmentieren'))
                ->setParameters(['slave' => $this->slaveParameter]),
            'activateDirectConnect' => (new Method('DirectConnect de-/aktivieren'))
                ->setParameters([
                    'slave' => $this->slaveParameter,
                    'active' => new BoolParameter('Aktiv'),
                ]),
        ]);
    }

    public function getEventClassName(): string
    {
        return IoEvent::class;
    }
}
