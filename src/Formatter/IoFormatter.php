<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use Exception;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Dto\Io\Direction as IoDirection;
use GibsonOS\Module\Hc\Dto\Io\Port;
use GibsonOS\Module\Hc\Mapper\IoMapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use ReflectionException;
use Throwable;

class IoFormatter extends AbstractHcFormatter
{
    private ?int $directConnectReadInputPort = null;

    public function __construct(
        TransformService $transformService,
        TwigService $twigService,
        TypeRepository $typeRepository,
        private ValueRepository $valueRepository,
        private AttributeRepository $attributeRepository,
        private LogRepository $logRepository,
        private IoMapper $ioMapper
    ) {
        parent::__construct($transformService, $twigService, $typeRepository);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function command(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case IoService::COMMAND_ADD_DIRECT_CONNECT:
                return 'DC hinzufügen';
            case IoService::COMMAND_SET_DIRECT_CONNECT:
                return 'DC überschreiben';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                return 'DC löschen';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                return 'DC reseten';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                return 'DC lesen';
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'DC defragmentieren';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                return 'Status in EEPROM';
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return 'DC aktiviert';
        }

        $module = $log->getModule();

        if ($module !== null && $log->getCommand() !== null && $log->getCommand() < (int) $module->getConfig()) {
            return $this->attributeRepository->loadDto(new Port($module, $log->getCommand() ?? 0))->getName();
        }

        return parent::command($log);
    }

    public function text(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case IoService::COMMAND_CONFIGURATION:
                return 'Port Anzahl: ' . $this->transformService->asciiToUnsignedInt($log->getRawData());
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'Defragmentieren';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                if ($log->getDirection() === Direction::OUTPUT) {
                    return null;
                }

                $lastByte = $this->transformService->asciiToUnsignedInt($log->getRawData(), 2);

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return 'Kein Port gesetzt';
                }

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return 'Kein Befehl vorhanden';
                }

                return null;
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return $this->transformService->asciiToUnsignedInt($log->getRawData(), 0) ? 'Aktiv' : 'Inaktiv';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                if ($log->getDirection() == Direction::OUTPUT) {
                    return 'Standard gesetzt';
                }

                if ($this->transformService->asciiToUnsignedInt($log->getRawData(), 0)) {
                    return 'Standard geladen';
                }

                return 'Standard nicht vorhanden';
        }

        return parent::text($log);
    }

    /**
     * @throws Exception
     */
    public function render(Log $log): ?string
    {
        $module = $log->getModule();

        if ($module === null) {
            return null;
        }

        switch ($log->getCommand()) {
            case IoService::COMMAND_STATUS:
            case IoService::COMMAND_DATA_CHANGED:
                $changedPorts = $this->getChangedPorts($log);

                if (!count($changedPorts)) {
                    return 'Keine Änderungen';
                }

                $return =
                    '<table>' .
                        '<tr>' .
                            '<th>&nbsp;</th>' .
                            '<th>Richtung</th>' .
                            '<th>Zustand</th>' .
                            '<th>PullUp</th>' .
                            '<th>Verzögerung</th>' .
                            '<th>PWM</th>' .
                            '<th>Blinken</th>' .
                        '</tr>';

                foreach ($changedPorts as $number => $port) {
                    $return .=
                        '<tr>' .
                            '<td>' . $port->getName() . '</td>' .
                            '<td>' . ($port->getDirection() === IoDirection::INPUT ? 'Eingang' : 'Ausgang') . '</td>' .
                            '<td>' . $port->getValueNames()[(int) $port->isValue()] . '</td>' .
                            ($port->getDirection() === IoDirection::INPUT
                                ? '<td>' . ($port->hasPullUp() ? 'Ja' : 'Nein') . '</td>' .
                                  '<td>' . $port->getDelay() . '</td>'
                                : '') .
                            ($port->getDirection() === IoDirection::OUTPUT
                                ? '<td>' . $port->getPwm() . '</td>' .
                                  '<td>' . $port->getBlink() . '</td>'
                                : '') .
                        '</tr>';
                }

                return $return . '</table>';
            case IoService::COMMAND_ADD_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $module->getTypeId(),
                    $inputPort,
                    [(int) $module->getId()],
                    IoService::ATTRIBUTE_TYPE_PORT,
                    IoService::ATTRIBUTE_PORT_KEY_NAME
                );

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $inputName[0]->getValue() . '</td>' .
                        '</tr>' .
                        $this->getDirectConnectTableRows($log, $inputPort, substr($log->getRawData(), 1)) .
                    '</table>';
            case IoService::COMMAND_SET_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $module->getTypeId(),
                    $inputPort,
                    [(int) $module->getId()],
                    IoService::ATTRIBUTE_TYPE_PORT,
                    IoService::ATTRIBUTE_PORT_KEY_NAME
                );

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $inputName[0]->getValue() . '</td>' .
                        '</tr>' .
                        '<tr>' .
                            '<th>Nummer</th>' .
                            '<td>' . $this->transformService->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                        '</tr>' .
                    $this->getDirectConnectTableRows($log, $inputPort, substr($log->getRawData(), 2)) .
                    '</table>';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $module->getTypeId(),
                    $inputPort,
                    [(int) $module->getId()],
                    IoService::ATTRIBUTE_TYPE_PORT,
                    IoService::ATTRIBUTE_PORT_KEY_NAME
                );

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $inputName[0]->getValue() . '</td>' .
                        '</tr>' .
                        '<tr>' .
                            '<th>Nummer</th>' .
                            '<td>' . $this->transformService->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                $inputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $module->getTypeId(),
                    $inputPort,
                    [(int) $module->getId()],
                    IoService::ATTRIBUTE_TYPE_PORT,
                    IoService::ATTRIBUTE_PORT_KEY_NAME
                );

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $inputName[0]->getValue() . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                if ($log->getDirection() === Direction::OUTPUT) {
                    $this->directConnectReadInputPort = $this->transformService->asciiToUnsignedInt($log->getRawData(), 0);
                    $inputName = $this->valueRepository->getByTypeId(
                        $module->getTypeId(),
                        $this->directConnectReadInputPort,
                        [(int) $module->getId()],
                        IoService::ATTRIBUTE_TYPE_PORT,
                        IoService::ATTRIBUTE_PORT_KEY_NAME
                    );

                    return
                        '<table>' .
                            '<tr>' .
                                '<th>Eingangs Port</th>' .
                                '<td>' . $inputName[0]->getValue() . '</td>' .
                            '</tr>' .
                            '<tr>' .
                                '<th>Nummer</th>' .
                                '<td>' . $this->transformService->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                            '</tr>' .
                        '</table>';
                }

                $lastByte = $this->transformService->asciiToUnsignedInt($log->getRawData(), 2);

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return null;
                }

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return null;
                }

                if ($this->directConnectReadInputPort === null) {
                    return null;
                }

                return
                    '<table>' .
                        $this->getDirectConnectTableRows(
                            $log,
                            $this->directConnectReadInputPort,
                            $log->getRawData()
                        ) .
                    '</table>';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                // return 'Status in EEPROM';
        }

        if ($log->getType() === MasterService::TYPE_DATA && $log->getCommand() < (int) $module->getConfig()) {
            $port = $this->ioMapper->getPort(new Port($module, $log->getCommand() ?? 0), $log->getRawData());
            $valueNames = $this->valueRepository->getByTypeId(
                $module->getTypeId(),
                $log->getCommand(),
                [(int) $module->getId()],
                IoService::ATTRIBUTE_TYPE_PORT,
                IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
            );

            $return =
                '<table>' .
                '<tr><th>Richtung</th><td>' . ($port->getDirection() === IoDirection::INPUT ? 'Eingang' : 'Ausgang') . '</td></tr>' .
                '<tr><th>Zustand</th><td>' . $port->getValueNames()[(int) $port->isValue()] . '</td></tr>';

            if ($port->getDirection() === IoDirection::INPUT) {
                $return .=
                    '<tr><th>PullUp</th><td>' . ($port->hasPullUp() ? 'Ja' : 'Nein') . '</td></tr>' .
                    '<tr><th>Verzögerung</th><td>' . $port->getDelay() . '</td></tr>';
            } else {
                $return .=
                    '<tr><th>PWM</th><td>' . $port->getPwm() . '</td></tr>' .
                    '<tr><th>Blinken</th><td>' . $port->getBlink() . '</td></tr>';
            }

            return $return . '</table>';
        }

        return parent::render($log);
    }

    /**
     * @throws SelectError
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     *
     * @return Port[]
     */
    private function getChangedPorts(Log $log): array
    {
        $module = $log->getModule();

        if ($module === null) {
            return [];
        }

        $ports = $this->ioMapper->getPorts($module, $log->getRawData(), (int) $module->getConfig());

        if ($log->getId() === 0) {
            return $ports;
        }

        try {
            $lastData = $this->logRepository->getPreviousEntryByModuleId(
                (int) $log->getId(),
                (int) $module->getId(),
                $log->getCommand(),
                $log->getType(),
                $log->getDirection()->value
            )->getRawData();
        } catch (SelectError) {
            return $ports;
        }

        $lastPorts = $this->ioMapper->getPorts($module, $lastData, (int) $module->getConfig());
        $changedPorts = [];

        foreach ($ports as $number => $port) {
            $lastPort = $lastPorts[$number];

            if ($lastPort->getDirection() !== $port->getDirection()) {
                $changedPorts[$number] = $port;

                continue;
            }

            if ($lastPort->isValue() !== $port->isValue()) {
                $changedPorts[$number] = $port;

                continue;
            }

            if ($port->getDirection() === IoDirection::INPUT) {
                if ($lastPort->getDelay() !== $port->getDelay()) {
                    $changedPorts[$number] = $port;

                    continue;
                }

                if ($lastPort->hasPullUp() !== $port->hasPullUp()) {
                    $changedPorts[$number] = $port;

                    continue;
                }
            } else {
                if ($lastPort->getPwm() !== $port->getPwm()) {
                    $changedPorts[$number] = $port;

                    continue;
                }

                if ($lastPort->getFadeIn() !== $port->getFadeIn()) {
                    $changedPorts[$number] = $port;

                    continue;
                }

                if ($lastPort->getBlink() !== $port->getBlink()) {
                    $changedPorts[$number] = $port;

                    continue;
                }
            }
        }

        return $changedPorts;
    }

    /**
     * @throws Exception
     */
    private function getDirectConnectTableRows(Log $log, int $inputPort, string $data): string
    {
        $module = $log->getModule();

        if ($module === null) {
            return '<tr></tr>';
        }

        $directConnect = $this->ioMapper->getDirectConnectAsArray($data);
        $moduleIds = [(int) $module->getId()];

        $inputValueNames = $this->valueRepository->getByTypeId(
            $module->getTypeId(),
            $inputPort,
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
        );
        $outputName = $this->valueRepository->getByTypeId(
            $module->getTypeId(),
            $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_NAME
        );
        $outputValueNames = $this->valueRepository->getByTypeId(
            $module->getTypeId(),
            $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
        );

        $addOrSub = 'Setzen';

        if ($directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB] == 1) {
            $addOrSub = 'Addieren';
        } elseif ($directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB] == -1) {
            $addOrSub = 'Subtrahieren';
        }

        return
            '<tr>' .
                '<th>Eingangs Zustand</th>' .
                '<td>' . $inputValueNames[$directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE]]->getValue() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Ausgangs Port</th>' .
                '<td>' . $outputName[0]->getValue() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>PWM</th>' .
                '<td>' . $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM] . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Blinken</th>' .
                '<td>' . $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK] . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Einblenden</th>' .
                '<td>' . $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN] . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Wert</th>' .
                '<td>' . $outputValueNames[$directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE]]->getValue() . '</td>' .
            '</tr>' .
            '<tr>' .
                '<th>Anwenden</th>' .
                '<td>' . $addOrSub . '</td>' .
            '</tr>';
    }
}
