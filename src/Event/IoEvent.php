<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Dto\Parameter\BoolParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\OptionParameter;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Dto\Parameter\Io\PortParameter;
use GibsonOS\Module\Hc\Dto\Parameter\SlaveParameter;
use GibsonOS\Module\Hc\Event\Describer\IoDescriber;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use Psr\Log\LoggerInterface;

#[Event('I/O')]
class IoEvent extends AbstractHcEvent
{
    #[Event\Trigger('Vor auslesen eines Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const BEFORE_READ_PORT = 'beforeReadPort';

    #[Event\Trigger('Nach auslesen eines Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const AFTER_READ_PORT = 'afterReadPort';

    #[Event\Trigger('Vor schreiben eines Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const BEFORE_WRITE_PORT = 'beforeWritePort';

    #[Event\Trigger('Nach schreiben eines Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const AFTER_WRITE_PORT = 'afterWritePort';

    #[Event\Trigger('Vor lesen der Ports aus EEPROM', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_READ_PORTS_FROM_EEPROM = 'beforeReadPortsFromEeprom';

    #[Event\Trigger('Nach lesen der Ports aus EEPROM', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_READ_PORTS_FROM_EEPROM = 'afterReadPortsFromEeprom';

    #[Event\Trigger('Vor schreiben der Ports in EEPROM', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_WRITE_PORTS_TO_EEPROM = 'beforeWritePortsToEeprom';

    #[Event\Trigger('Nach schreiben der Ports in EEPROM', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_WRITE_PORTS_TO_EEPROM = 'afterWritePortsToEeprom';

    #[Event\Trigger('Vor lesen der Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_READ_PORTS = 'beforeReadPorts';

    #[Event\Trigger('Nach lesen der Ports', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_READ_PORTS = 'afterReadPorts';

    #[Event\Trigger('Vor hinzufügen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
    ])]
    public const BEFORE_ADD_DIRECT_CONNECT = 'beforeAddDirectConnect';

    #[Event\Trigger('Nach hinzufügen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
    ])]
    public const AFTER_ADD_DIRECT_CONNECT = 'afterAddDirectConnect';

    #[Event\Trigger('Vor überschreiben eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
    ])]
    public const BEFORE_SET_DIRECT_CONNECT = 'beforeSetDirectConnect';

    #[Event\Trigger('Nach überschreiben eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
    ])]
    public const AFTER_SET_DIRECT_CONNECT = 'afterSetDirectConnect';

    #[Event\Trigger('Vor speichern eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
    ])]
    public const BEFORE_SAVE_DIRECT_CONNECT = 'beforeSaveDirectConnect';

    #[Event\Trigger('Nach speichern eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
    ])]
    public const AFTER_SAVE_DIRECT_CONNECT = 'afterSaveDirectConnect';

    #[Event\Trigger('Vor lesen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
    ])]
    public const BEFORE_READ_DIRECT_CONNECT = 'beforeReadDirectConnect';

    #[Event\Trigger('Nach lesen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE, 'className' => BoolParameter::class, 'title' => 'Eingangsport Geschloßen'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT, 'className' => IntParameter::class, 'title' => 'Ausgangsport'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Ausgangsport An'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'Ausgangsport PWM'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Ausgangsport Blinken'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Ausgangsport Einblenden'],
        ['key' => IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB, 'className' => IntParameter::class, 'title' => 'Addieren oder subtrahieren'],
        ['key' => 'hasMore', 'className' => BoolParameter::class, 'title' => 'Es gibt weitere DirectConnects'],
    ])]
    public const AFTER_READ_DIRECT_CONNECT = 'afterReadDirectConnect';

    #[Event\Trigger('Vor löschen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
    ])]
    public const BEFORE_DELETE_DIRECT_CONNECT = 'beforeDeleteDirectConnect';

    #[Event\Trigger('Nach löschen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
    ])]
    public const AFTER_DELETE_DIRECT_CONNECT = 'afterDeleteDirectConnect';

    #[Event\Trigger('Vor löschen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'databaseOnly', 'className' => BoolParameter::class, 'title' => 'Nur Datenbank'],
    ])]
    public const BEFORE_RESET_DIRECT_CONNECT = 'beforeResetDirectConnect';

    #[Event\Trigger('Nach löschen eines DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'databaseOnly', 'className' => BoolParameter::class, 'title' => 'Nur Datenbank'],
    ])]
    public const AFTER_RESET_DIRECT_CONNECT = 'afterResetDirectConnect';

    #[Event\Trigger('Vor defragmentieren der DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_DEFRAGMENT_DIRECT_CONNECT = 'beforeDefragmentDirectConnect';

    #[Event\Trigger('Nach defragmentieren der DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_DEFRAGMENT_DIRECT_CONNECT = 'afterDefragmentDirectConnect';

    #[Event\Trigger('Vor de-/aktivieren der DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'active', 'className' => BoolParameter::class, 'title' => 'Aktiv'],
    ])]
    public const BEFORE_ACTIVATE_DIRECT_CONNECT = 'beforeActivateDirectConnect';

    #[Event\Trigger('Nach de-/aktivieren der DirectConnects', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'active', 'className' => BoolParameter::class, 'title' => 'Aktiv'],
    ])]
    public const AFTER_ACTIVATE_DIRECT_CONNECT = 'afterActivateDirectConnect';

    #[Event\Trigger('Vor prüfen ob DirectConnects aktiv', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_IS_DIRECT_CONNECT_ACTIVE = 'beforeIsDirectConnectActive';

    #[Event\Trigger('Nach prüfen ob DirectConnects aktiv', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_IS_DIRECT_CONNECT_ACTIVE = 'afterIsDirectConnectActive';

    public function __construct(
        IoDescriber $describer,
        ServiceManagerService $serviceManagerService,
        TypeRepository $typeRepository,
        LoggerInterface $logger,
        private IoService $ioService
    ) {
        parent::__construct($describer, $serviceManagerService, $typeRepository, $logger, $this->ioService);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Port lesen')]
    #[Event\ReturnValue(OptionParameter::class, 'Richtung', ['options' => [
        IoService::DIRECTION_INPUT => 'Eingang',
        IoService::DIRECTION_OUTPUT => 'Ausgang',
    ]], IoService::ATTRIBUTE_PORT_KEY_DIRECTION)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Wert', key: IoService::ATTRIBUTE_PORT_KEY_VALUE)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Verzögerung', key: IoService::ATTRIBUTE_PORT_KEY_DELAY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'PullUp', key: IoService::ATTRIBUTE_PORT_KEY_PULL_UP)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'PWM', key: IoService::ATTRIBUTE_PORT_KEY_PWM)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Fade In', key: IoService::ATTRIBUTE_PORT_KEY_FADE_IN)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Blink', key: IoService::ATTRIBUTE_PORT_KEY_BLINK)]
    public function readPort(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(PortParameter::class)] int $number
    ): array {
        return $this->ioService->readPort($slave, $number);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readPortsFromEeprom(Module $slave): void
    {
        $this->ioService->readPortsFromEeprom($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function getPorts(Module $slave): array
    {
        return $this->ioService->getPorts($slave);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function readDirectConnect(Module $slave, array $params): array
    {
        return $this->ioService->readDirectConnect($slave, $params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    public function isDirectConnectActive(Module $slave): bool
    {
        return $this->ioService->isDirectConnectActive($slave);
    }

    /**
     * @throws AbstractException
     */
    public function setPort(Module $slave, array $params): void
    {
        $this->ioService->setPort(
            $slave,
            $params['number'],
            $params[IoService::ATTRIBUTE_PORT_KEY_NAME],
            $params[IoService::ATTRIBUTE_PORT_KEY_DIRECTION],
            $params[IoService::ATTRIBUTE_PORT_KEY_PULL_UP],
            $params[IoService::ATTRIBUTE_PORT_KEY_DELAY],
            $params[IoService::ATTRIBUTE_PORT_KEY_PWM],
            $params[IoService::ATTRIBUTE_PORT_KEY_BLINK],
            $params[IoService::ATTRIBUTE_PORT_KEY_FADE_IN],
            $params[IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES]
        );
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function writePortsToEeprom(Module $slave): void
    {
        $this->ioService->writePortsToEeprom($slave);
    }

    /**
     * @throws AbstractException
     */
    public function saveDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->saveDirectConnect(
            $slave,
            $params['inputPort'],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE],
            $params['order'],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN],
            $params[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB]
        );
    }

    /**
     * @throws AbstractException
     */
    public function deleteDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->deleteDirectConnect($slave, $params['port'], $params['order']);
    }

    /**
     * @throws AbstractException
     */
    public function resetDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->resetDirectConnect($slave, $params['port'], $params['databaseOnly']);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function defragmentDirectConnect(Module $slave): void
    {
        $this->ioService->defragmentDirectConnect($slave);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    public function activateDirectConnect(Module $slave, array $params): void
    {
        $this->ioService->activateDirectConnect($slave, $params['active']);
    }
}
