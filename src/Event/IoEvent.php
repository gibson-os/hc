<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Dto\Parameter\BoolParameter;
use GibsonOS\Core\Dto\Parameter\IntParameter;
use GibsonOS\Core\Dto\Parameter\OptionParameter;
use GibsonOS\Core\Dto\Parameter\StringParameter;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\EventException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\FcmService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Dto\Parameter\Io\PortParameter;
use GibsonOS\Module\Hc\Dto\Parameter\ModuleParameter;
use GibsonOS\Module\Hc\Enum\Io\AddOrSub;
use GibsonOS\Module\Hc\Enum\Io\Direction;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Io\DirectConnectRepository;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Module\IoService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use Psr\Log\LoggerInterface;
use ReflectionException;

#[Event('I/O')]
#[Event\Listener('port', 'module', ['params' => [
    'paramKey' => 'moduleId',
    'recordKey' => 'id',
]])]
#[Event\ParameterOption('module', 'typeHelper', 'io')]
class IoEvent extends AbstractHcEvent
{
    #[Event\Trigger('Vor auslesen eines Ports', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_NAME, 'className' => StringParameter::class, 'title' => 'Name'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_DIRECTION, 'className' => OptionParameter::class, 'options' => [
            'options' => [[
                0 => 'Eingang',
                1 => 'Ausgang',
            ]],
        ], 'title' => 'Richtung'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Zustand'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_PULL_UP, 'className' => BoolParameter::class, 'title' => 'PullUp'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'PWM'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Blinken'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Einblenden'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_DELAY, 'className' => IntParameter::class, 'title' => 'Verzögerung'],
    ])]
    public const BEFORE_READ_PORT = 'beforeReadPort';

    #[Event\Trigger('Nach auslesen eines Ports', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_NAME, 'className' => StringParameter::class, 'title' => 'Name'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_DIRECTION, 'className' => OptionParameter::class, 'options' => [
            'options' => [[
                0 => 'Eingang',
                1 => 'Ausgang',
            ]],
        ], 'title' => 'Richtung'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_VALUE, 'className' => BoolParameter::class, 'title' => 'Zustand'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_PULL_UP, 'className' => BoolParameter::class, 'title' => 'PullUp'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_PWM, 'className' => IntParameter::class, 'title' => 'PWM'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_BLINK, 'className' => IntParameter::class, 'title' => 'Blinken'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_FADE_IN, 'className' => IntParameter::class, 'title' => 'Einblenden'],
        ['key' => IoService::ATTRIBUTE_PORT_KEY_DELAY, 'className' => IntParameter::class, 'title' => 'Verzögerung'],
    ])]
    public const AFTER_READ_PORT = 'afterReadPort';

    #[Event\Trigger('Vor schreiben eines Ports', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const BEFORE_WRITE_PORT = 'beforeWritePort';

    #[Event\Trigger('Nach schreiben eines Ports', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
    ])]
    public const AFTER_WRITE_PORT = 'afterWritePort';

    #[Event\Trigger('Vor lesen der Ports aus EEPROM', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_READ_PORTS_FROM_EEPROM = 'beforeReadPortsFromEeprom';

    #[Event\Trigger('Nach lesen der Ports aus EEPROM', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_READ_PORTS_FROM_EEPROM = 'afterReadPortsFromEeprom';

    #[Event\Trigger('Vor schreiben der Ports in EEPROM', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_WRITE_PORTS_TO_EEPROM = 'beforeWritePortsToEeprom';

    #[Event\Trigger('Nach schreiben der Ports in EEPROM', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_WRITE_PORTS_TO_EEPROM = 'afterWritePortsToEeprom';

    #[Event\Trigger('Vor lesen der Ports', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_READ_PORTS = 'beforeReadPorts';

    #[Event\Trigger('Nach lesen der Ports', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_READ_PORTS = 'afterReadPorts';

    #[Event\Trigger('Vor hinzufügen eines DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
    ])]
    public const BEFORE_READ_DIRECT_CONNECT = 'beforeReadDirectConnect';

    #[Event\Trigger('Nach lesen eines DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
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
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
    ])]
    public const BEFORE_DELETE_DIRECT_CONNECT = 'beforeDeleteDirectConnect';

    #[Event\Trigger('Nach löschen eines DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'order', 'className' => IntParameter::class, 'title' => 'Position'],
    ])]
    public const AFTER_DELETE_DIRECT_CONNECT = 'afterDeleteDirectConnect';

    #[Event\Trigger('Vor löschen eines DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'databaseOnly', 'className' => BoolParameter::class, 'title' => 'Nur Datenbank'],
    ])]
    public const BEFORE_RESET_DIRECT_CONNECT = 'beforeResetDirectConnect';

    #[Event\Trigger('Nach löschen eines DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'port', 'className' => PortParameter::class],
        ['key' => 'databaseOnly', 'className' => BoolParameter::class, 'title' => 'Nur Datenbank'],
    ])]
    public const AFTER_RESET_DIRECT_CONNECT = 'afterResetDirectConnect';

    #[Event\Trigger('Vor defragmentieren der DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_DEFRAGMENT_DIRECT_CONNECT = 'beforeDefragmentDirectConnect';

    #[Event\Trigger('Nach defragmentieren der DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_DEFRAGMENT_DIRECT_CONNECT = 'afterDefragmentDirectConnect';

    #[Event\Trigger('Vor de-/aktivieren der DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'active', 'className' => BoolParameter::class, 'title' => 'Aktiv'],
    ])]
    public const BEFORE_ACTIVATE_DIRECT_CONNECT = 'beforeActivateDirectConnect';

    #[Event\Trigger('Nach de-/aktivieren der DirectConnects', [
        ['key' => 'module', 'className' => ModuleParameter::class],
        ['key' => 'active', 'className' => BoolParameter::class, 'title' => 'Aktiv'],
    ])]
    public const AFTER_ACTIVATE_DIRECT_CONNECT = 'afterActivateDirectConnect';

    #[Event\Trigger('Vor prüfen ob DirectConnects aktiv', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_IS_DIRECT_CONNECT_ACTIVE = 'beforeIsDirectConnectActive';

    #[Event\Trigger('Nach prüfen ob DirectConnects aktiv', [
        ['key' => 'module', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_IS_DIRECT_CONNECT_ACTIVE = 'afterIsDirectConnectActive';

    public function __construct(
        EventService $eventService,
        ReflectionManager $reflectionManager,
        TypeRepository $typeRepository,
        LoggerInterface $logger,
        FcmService $fcmService,
        private readonly IoService $ioService,
        private readonly PortRepository $portRepository,
        private readonly DirectConnectRepository $directConnectRepository,
        private readonly ModelWrapper $modelWrapper,
    ) {
        parent::__construct($eventService, $reflectionManager, $typeRepository, $logger, $fcmService, $this->ioService);
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     */
    #[Event\Method('Port lesen')]
    #[Event\ReturnValue(OptionParameter::class, 'Richtung', ['options' => [[
        0 => 'Eingang',
        1 => 'Ausgang',
    ]]], IoService::ATTRIBUTE_PORT_KEY_DIRECTION)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Wert', key: IoService::ATTRIBUTE_PORT_KEY_VALUE)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Verzögerung', key: IoService::ATTRIBUTE_PORT_KEY_DELAY)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'PullUp', key: IoService::ATTRIBUTE_PORT_KEY_PULL_UP)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'PWM', key: IoService::ATTRIBUTE_PORT_KEY_PWM)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Fade In', key: IoService::ATTRIBUTE_PORT_KEY_FADE_IN)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Blink', key: IoService::ATTRIBUTE_PORT_KEY_BLINK)]
    public function readPort(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
    ): Port {
        return $this->ioService->readPort($port);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Ports aus EEPROM lesen')]
    public function readPortsFromEeprom(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
    ): void {
        $this->ioService->readPortsFromEeprom($module);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws ClientException
     * @throws RecordException
     */
    #[Event\Method('Ports auslesen')]
    public function getPorts(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
    ): array {
        return $this->portRepository->getByModule($module);
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('DirectConnect lesen')]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Eingangsport geschloßen', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Ausgangsport', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Ausgangsport An', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Ausgangsport PWM', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Ausgangsport Blinken', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Ausgangsport Einblenden', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN)]
    #[Event\ReturnValue(className: IntParameter::class, title: 'Addieren oder subtrahieren', key: IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB)]
    #[Event\ReturnValue(className: BoolParameter::class, title: 'Es gibt weitere DirectConnects', key: 'hasMore')]
    public function readDirectConnect(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(IntParameter::class, 'Reihenfolge')]
        int $order,
    ): array {
        return $this->ioService->readDirectConnect($module, $port, $order)->getDirectConnect()?->jsonSerialize() ?? [];
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     */
    #[Event\Method('Ist DirectConnect aktiv')]
    #[Event\ReturnValue(BoolParameter::class, 'Aktiv')]
    public function isDirectConnectActive(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
    ): bool {
        return $this->ioService->isDirectConnectActive($module);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port setzen')]
    public function setPort(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(StringParameter::class, 'Name')]
        string $name,
        #[Event\Parameter(OptionParameter::class, 'Richtung', ['options' => [[
            0 => 'Eingang',
            1 => 'Ausgang',
        ]]])]
        int $direction,
        #[Event\Parameter(BoolParameter::class, 'PullUp')]
        bool $pullUp,
        #[Event\Parameter(IntParameter::class, 'Verzögerung')]
        int $delay,
        #[Event\Parameter(IntParameter::class, 'PWM')]
        int $pwm,
        #[Event\Parameter(IntParameter::class, 'Blink')]
        int $blink,
        #[Event\Parameter(IntParameter::class, 'Fade In')]
        int $fadeIn,
        #[Event\Parameter(StringParameter::class, 'Werte Name')]
        string $valueNames,
    ): void {
        $port
            ->setName($name)
            ->setDirection(Direction::from($direction))
            ->setPullUp($pullUp)
            ->setDelay($delay)
            ->setPwm($pwm)
            ->setBlink($blink)
            ->setFadeIn($fadeIn)
            ->setValueNames(explode(', ', $valueNames))
        ;
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port Zustand setzen')]
    public function setValue(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(BoolParameter::class, 'Zustand')]
        bool $value,
    ): void {
        $port->setValue($value);
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port Fade In setzen')]
    public function setFadeIn(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(IntParameter::class, 'Fade In')]
        int $fadeIn,
    ): void {
        $port->setFadeIn($fadeIn);
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port Blinken setzen')]
    public function setBlink(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(IntParameter::class, 'Blink')]
        int $blink,
    ): void {
        $port->setBlink($blink);
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port PWM setzen')]
    public function setPwm(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(IntParameter::class, 'Fade In')]
        int $pwm,
    ): void {
        $port->setBlink($pwm);
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port Delay setzen')]
    public function setDelay(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(IntParameter::class, 'Verzögerung')]
        int $delay,
    ): void {
        $port->setDelay($delay);
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('Port PullUp setzen')]
    public function setPullUp(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(BoolParameter::class, 'PullUp')]
        bool $pullUp,
    ): void {
        $port->setPullUp($pullUp);
        $this->ioService->setPort($port);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('Ports in EEPROM schreiben')]
    public function writePortsToEeprom(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
    ): void {
        $this->ioService->writePortsToEeprom($module);
    }

    /**
     * @throws AbstractException
     */
    #[Event\Method('DirectConnect speichern')]
    #[Event\Listener('outputPort', 'module', ['params' => [
        'paramKey' => 'moduleId',
        'recordKey' => 'id',
    ]])]
    public function saveDirectConnect(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class, 'Eingangsport')]
        Port $inputPort,
        #[Event\Parameter(BoolParameter::class, 'Eingangsport geschloßen')]
        bool $inputValue,
        #[Event\Parameter(IntParameter::class, 'Reihenfolge')]
        int $order,
        #[Event\Parameter(PortParameter::class, 'Ausgangsport')]
        Port $outputPort,
        #[Event\Parameter(BoolParameter::class, 'Ausgangsport An')]
        bool $value,
        #[Event\Parameter(IntParameter::class, 'Ausgangsport PWM')]
        int $pwm,
        #[Event\Parameter(IntParameter::class, 'Ausgangsport Blinken')]
        int $blink,
        #[Event\Parameter(IntParameter::class, 'Ausgangsport Fade In')]
        int $fadeIn,
        #[Event\Parameter(IntParameter::class, 'Addieren oder subtrahieren')]
        int $addOrSub,
    ): void {
        $this->ioService->saveDirectConnect(
            $module,
            (new DirectConnect($this->modelWrapper))
                ->setInputPort($inputPort)
                ->setOutputPort($outputPort)
                ->setInputValue($inputValue)
                ->setOrder($order)
                ->setValue($value)
                ->setPwm($pwm)
                ->setBlink($blink)
                ->setFadeIn($fadeIn)
                ->setAddOrSub(AddOrSub::from($addOrSub)),
        );
    }

    /**
     * @throws AbstractException
     * @throws WriteException
     * @throws JsonException
     */
    #[Event\Method('DirectConnect löschen')]
    public function deleteDirectConnect(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(IntParameter::class, 'Reihenfolge')]
        int $order,
    ): void {
        $this->ioService->deleteDirectConnect($module, $this->directConnectRepository->getByOrder($port, $order));
    }

    /**
     * @throws AbstractException
     * @throws WriteException
     */
    #[Event\Method('Alle DirectConnects löschen')]
    public function resetDirectConnect(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(PortParameter::class)]
        Port $port,
        #[Event\Parameter(BoolParameter::class, 'Nur Datenbank')]
        bool $databaseOnly,
    ): void {
        $this->ioService->resetDirectConnect($module, $port, $databaseOnly);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('DirectConnect defragmentieren')]
    public function defragmentDirectConnect(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
    ): void {
        $this->ioService->defragmentDirectConnect($module);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws EventException
     * @throws FactoryError
     * @throws SaveError
     * @throws WriteException
     */
    #[Event\Method('DirectConnect de-/aktivieren')]
    public function activateDirectConnect(
        #[Event\Parameter(ModuleParameter::class)]
        Module $module,
        #[Event\Parameter(BoolParameter::class, 'Aktiv')]
        bool $active,
    ): void {
        $this->ioService->activateDirectConnect($module, $active);
    }
}
