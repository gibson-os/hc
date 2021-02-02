<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Mapper\IoMapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Service\TransformService;

class IoFormatter extends AbstractHcFormatter
{
    private ?int $directConnectReadInputPort = null;

    private ValueRepository $valueRepository;

    private LogRepository $logRepository;

    private IoMapper $ioMapper;

    public function __construct(
        TransformService $transform,
        ValueRepository $valueRepository,
        LogRepository $logRepository,
        IoMapper $ioMapper
    ) {
        parent::__construct($transform);
        $this->valueRepository = $valueRepository;
        $this->logRepository = $logRepository;
        $this->ioMapper = $ioMapper;
    }

    /**
     * @throws Exception
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

        if ($log->getCommand() !== null && $log->getCommand() < (int) $log->getModule()->getConfig()) {
            $name = $this->valueRepository->getByTypeId(
                $log->getModule()->getTypeId(),
                $log->getCommand(),
                [(int) $log->getModule()->getId()],
                IoService::ATTRIBUTE_TYPE_PORT,
                IoService::ATTRIBUTE_PORT_KEY_NAME
            );

            return $name[0]->getValue();
        }

        return parent::command($log);
    }

    public function text(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case IoService::COMMAND_CONFIGURATION:
                return 'Port Anzahl: ' . $this->transform->asciiToUnsignedInt($log->getRawData());
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'Defragmentieren';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                if ($log->getDirection() === Log::DIRECTION_OUTPUT) {
                    return null;
                }

                $lastByte = $this->transform->asciiToUnsignedInt($log->getRawData(), 2);

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return 'Kein Port gesetzt';
                }

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return 'Kein Befehl vorhanden';
                }

                return null;
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return $this->transform->asciiToUnsignedInt($log->getRawData(), 0) ? 'Aktiv' : 'Inaktiv';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                if ($log->getDirection() == Log::DIRECTION_OUTPUT) {
                    return 'Standard gesetzt';
                }

                if ($this->transform->asciiToUnsignedInt($log->getRawData(), 0)) {
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
                    $name = $this->valueRepository->getByTypeId(
                        $log->getModule()->getTypeId(),
                        (int) $number,
                        [(int) $log->getModule()->getId()],
                        IoService::ATTRIBUTE_TYPE_PORT,
                        IoService::ATTRIBUTE_PORT_KEY_NAME
                    );
                    $valueNames = $this->valueRepository->getByTypeId(
                        $log->getModule()->getTypeId(),
                        (int) $number,
                        [(int) $log->getModule()->getId()],
                        IoService::ATTRIBUTE_TYPE_PORT,
                        IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
                    );

                    $return .=
                        '<tr>' .
                            '<td>' . $name[0]->getValue() . '</td>' .
                            '<td>' . ($port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] === IoService::DIRECTION_INPUT ? 'Eingang' : 'Ausgang') . '</td>' .
                            '<td>' . $valueNames[$port[IoService::ATTRIBUTE_PORT_KEY_VALUE]]->getValue() . '</td>' .
                            ($port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] === IoService::DIRECTION_INPUT
                                ? '<td>' . ($port[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] ? 'Ja' : 'Nein') . '</td>' .
                                  '<td>' . $port[IoService::ATTRIBUTE_PORT_KEY_DELAY] . '</td>'
                                : '') .
                            ($port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] === IoService::DIRECTION_OUTPUT
                                ? '<td>' . (isset($port[IoService::ATTRIBUTE_PORT_KEY_PWM]) ? $port[IoService::ATTRIBUTE_PORT_KEY_PWM] : 0) . '</td>' .
                                  '<td>' . (isset($port[IoService::ATTRIBUTE_PORT_KEY_BLINK]) ? $port[IoService::ATTRIBUTE_PORT_KEY_BLINK] : 0) . '</td>'
                                : '') .
                        '</tr>';
                }

                return $return . '</table>';
            case IoService::COMMAND_ADD_DIRECT_CONNECT:
                $inputPort = $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $log->getModule()->getTypeId(),
                    $inputPort,
                    [(int) $log->getModule()->getId()],
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
                $inputPort = $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $log->getModule()->getTypeId(),
                    $inputPort,
                    [(int) $log->getModule()->getId()],
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
                            '<td>' . $this->transform->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                        '</tr>' .
                    $this->getDirectConnectTableRows($log, $inputPort, substr($log->getRawData(), 2)) .
                    '</table>';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                $inputPort = $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $log->getModule()->getTypeId(),
                    $inputPort,
                    [(int) $log->getModule()->getId()],
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
                            '<td>' . $this->transform->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                $inputPort = $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
                $inputName = $this->valueRepository->getByTypeId(
                    $log->getModule()->getTypeId(),
                    $inputPort,
                    [(int) $log->getModule()->getId()],
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
                if ($log->getDirection() === Log::DIRECTION_OUTPUT) {
                    $this->directConnectReadInputPort = $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
                    $inputName = $this->valueRepository->getByTypeId(
                        $log->getModule()->getTypeId(),
                        $this->directConnectReadInputPort,
                        [(int) $log->getModule()->getId()],
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
                                '<td>' . $this->transform->asciiToUnsignedInt($log->getRawData(), 1) . '</td>' .
                            '</tr>' .
                        '</table>';
                }

                $lastByte = $this->transform->asciiToUnsignedInt($log->getRawData(), 2);

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
                //return 'Status in EEPROM';
        }

        if ($log->getType() === MasterService::TYPE_DATA && $log->getCommand() < (int) $log->getModule()->getConfig()) {
            $port = $this->ioMapper->getPortAsArray($log->getRawData());
            $valueNames = $this->valueRepository->getByTypeId(
                $log->getModule()->getTypeId(),
                $log->getCommand(),
                [(int) $log->getModule()->getId()],
                IoService::ATTRIBUTE_TYPE_PORT,
                IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
            );

            $return =
                '<table>' .
                '<tr><th>Richtung</th><td>' . ($port['direction'] == IoService::DIRECTION_INPUT ? 'Eingang' : 'Ausgang') . '</td></tr>' .
                '<tr><th>Zustand</th><td>' . $valueNames[$port['value']]->getValue() . '</td></tr>';

            if ($port['direction'] == IoService::DIRECTION_INPUT) {
                $return .=
                    '<tr><th>PullUp</th><td>' . ($port['pullUp'] ? 'Ja' : 'Nein') . '</td></tr>' .
                    '<tr><th>Verzögerung</th><td>' . $port['delay'] . '</td></tr>';
            } else {
                $return .=
                    '<tr><th>PWM</th><td>' . $port['pwm'] . '</td></tr>' .
                    '<tr><th>Blinken</th><td>' . $port['blink'] . '</td></tr>';
            }

            return $return . '</table>';
        }

        return parent::render($log);
    }

    /**
     * @throws DateTimeError
     */
    private function getChangedPorts(Log $log): array
    {
        $ports = $this->ioMapper->getPortsAsArray($log->getRawData(), (int) $log->getModule()->getConfig());

        if ($log->getId() === 0) {
            return $ports;
        }

        try {
            $lastData = $this->logRepository->getPreviewEntryByModuleId(
                (int) $log->getId(),
                (int) $log->getModule()->getId(),
                $log->getCommand(),
                $log->getType(),
                $log->getDirection()
            )->getRawData();
        } catch (SelectError $e) {
            return $ports;
        }

        $lastPorts = $this->ioMapper->getPortsAsArray($lastData, (int) $log->getModule()->getConfig());
        $changedPorts = [];

        foreach ($ports as $number => $port) {
            $lastPort = $lastPorts[$number];

            if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] !== $port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION]) {
                $changedPorts[$number] = $port;

                continue;
            }

            if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_VALUE] !== $port[IoService::ATTRIBUTE_PORT_KEY_VALUE]) {
                $changedPorts[$number] = $port;

                continue;
            }

            if ($port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] == IoService::DIRECTION_INPUT) {
                if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_DELAY] !== $port[IoService::ATTRIBUTE_PORT_KEY_DELAY]) {
                    $changedPorts[$number] = $port;

                    continue;
                }

                if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] !== $port[IoService::ATTRIBUTE_PORT_KEY_PULL_UP]) {
                    $changedPorts[$number] = $port;

                    continue;
                }
            } else {
                if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_PWM] !== $port[IoService::ATTRIBUTE_PORT_KEY_PWM]) {
                    $changedPorts[$number] = $port;

                    continue;
                }

                if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_FADE_IN] !== $port[IoService::ATTRIBUTE_PORT_KEY_FADE_IN]) {
                    $changedPorts[$number] = $port;

                    continue;
                }

                if ($lastPort[IoService::ATTRIBUTE_PORT_KEY_BLINK] !== $port[IoService::ATTRIBUTE_PORT_KEY_BLINK]) {
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
        $directConnect = $this->ioMapper->getDirectConnectAsArray($data);
        $moduleIds = [(int) $log->getModule()->getId()];

        $inputValueNames = $this->valueRepository->getByTypeId(
            $log->getModule()->getTypeId(),
            $inputPort,
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
        );
        $outputName = $this->valueRepository->getByTypeId(
            $log->getModule()->getTypeId(),
            $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_NAME
        );
        $outputValueNames = $this->valueRepository->getByTypeId(
            $log->getModule()->getTypeId(),
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
