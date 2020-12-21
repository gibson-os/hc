<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;
use GibsonOS\Module\Hc\Service\TransformService;

class IoFormatter extends AbstractHcFormatter
{
    private ?int $directConnectReadInputPort = null;

    private ValueRepository $valueRepository;

    private LogRepository $logRepository;

    public function __construct(
        TransformService $transform,
        ValueRepository $valueRepository,
        LogRepository $logRepository
    ) {
        parent::__construct($transform);
        $this->valueRepository = $valueRepository;
        $this->logRepository = $logRepository;
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

        if ($log->getCommand() < (int) $log->getModule()->getConfig()) {
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
                return 'Port Anzahl: ' . $this->transform->hexToInt($log->getData());
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'Defragmentieren';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                if ($log->getDirection() === Log::DIRECTION_OUTPUT) {
                    return null;
                }

                $lastByte = $this->transform->hexToInt($log->getData(), 2);

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return 'Kein Port gesetzt';
                }

                if ($lastByte === IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return 'Kein Befehl vorhanden';
                }

                return null;
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return $this->transform->hexToInt($log->getData(), 0) ? 'Aktiv' : 'Inaktiv';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                if ($log->getDirection() == Log::DIRECTION_OUTPUT) {
                    return 'Standard gesetzt';
                }

                if ($this->transform->hexToInt($log->getData(), 0)) {
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
                $inputPort = $this->transform->hexToInt($log->getData(), 0);
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
                        $this->getDirectConnectTableRows($log, $inputPort, substr($this->transform->hexToAscii($log->getData()), 1)) .
                    '</table>';
            case IoService::COMMAND_SET_DIRECT_CONNECT:
                $inputPort = $this->transform->hexToInt($log->getData(), 0);
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
                            '<td>' . $this->transform->hexToInt($log->getData(), 1) . '</td>' .
                        '</tr>' .
                    $this->getDirectConnectTableRows($log, $inputPort, substr($this->transform->hexToAscii($log->getData()), 2)) .
                    '</table>';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                $inputPort = $this->transform->hexToInt($log->getData(), 0);
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
                            '<td>' . $this->transform->hexToInt($log->getData(), 1) . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                $inputPort = $this->transform->hexToInt($log->getData(), 0);
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
                    $this->directConnectReadInputPort = $this->transform->hexToInt($log->getData(), 0);
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
                                '<td>' . $this->transform->hexToInt($log->getData(), 1) . '</td>' .
                            '</tr>' .
                        '</table>';
                }

                $lastByte = $this->transform->hexToInt($log->getData(), 2);

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
                            $this->transform->hexToAscii($log->getData())
                        ) .
                    '</table>';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                //return 'Status in EEPROM';
        }

        if ($log->getCommand() < (int) $log->getModule()->getConfig()) {
            $port = self::getPortAsArray($this->transform->hexToAscii($log->getData()));
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
        $ports = $this->getPortsAsArray($this->transform->hexToAscii($log->getData()), (int) $log->getModule()->getConfig());

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
            )->getData();
        } catch (SelectError $e) {
            return $ports;
        }

        $lastPorts = self::getPortsAsArray($this->transform->hexToAscii($lastData), (int) $log->getModule()->getConfig());
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
        $directConnect = self::getDirectConnectAsArray($data);
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

    public function getPortAsArray(string $data): array
    {
        $byte1 = $this->transform->asciiToUnsignedInt($data, 0);
        $byte2 = $this->transform->asciiToUnsignedInt($data, 1);

        $port = [
            IoService::ATTRIBUTE_PORT_KEY_DIRECTION => $byte1 & 1,
            IoService::ATTRIBUTE_PORT_KEY_VALUE => $byte1 >> 2 & 1,
        ];

        if ($port[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] == IoService::DIRECTION_INPUT) {
            $port[IoService::ATTRIBUTE_PORT_KEY_DELAY] = ($byte1 >> 3) | $byte2;
            $port[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] = $byte1 >> 1 & 1;
        } else {
            $port[IoService::ATTRIBUTE_PORT_KEY_PWM] = $byte2;
            $port[IoService::ATTRIBUTE_PORT_KEY_FADE_IN] = 0;

            if ($port[IoService::ATTRIBUTE_PORT_KEY_VALUE]) {
                $port[IoService::ATTRIBUTE_PORT_KEY_FADE_IN] = $port[IoService::ATTRIBUTE_PORT_KEY_PWM];
                $port[IoService::ATTRIBUTE_PORT_KEY_PWM] = 0;
            }

            $port[IoService::ATTRIBUTE_PORT_KEY_BLINK] = $byte1 >> 3;
        }

        return $port;
    }

    public function getPortsAsArray(string $data, int $portCount): array
    {
        $byteCount = 0;
        $ports = [];

        for ($i = 0; $i < $portCount; ++$i) {
            $ports[$i] = $this->getPortAsArray(substr($data, $byteCount, 2));
            $byteCount += 2;
        }

        return $ports;
    }

    public function getPortAsString(array $data): string
    {
        if ($data[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] == IoService::DIRECTION_INPUT) {
            return
                chr(
                    $data[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] << 1) |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_VALUE] << 2) |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_DELAY] << 3)
                ) .
                chr($data[IoService::ATTRIBUTE_PORT_KEY_DELAY]);
        }
        $pwm = $data[IoService::ATTRIBUTE_PORT_KEY_PWM];

        if ($data[IoService::ATTRIBUTE_PORT_KEY_VALUE]) {
            $pwm = $data[IoService::ATTRIBUTE_PORT_KEY_FADE_IN];
        }

        return
                chr(
                    $data[IoService::ATTRIBUTE_PORT_KEY_DIRECTION] |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_PULL_UP] << 1) |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_VALUE] << 2) |
                    ($data[IoService::ATTRIBUTE_PORT_KEY_BLINK] << 3)
                ) .
                chr($pwm);
    }

    public function getDirectConnectAsArray(string $data): array
    {
        $inputValueAndOutputPortByte = $this->transform->asciiToUnsignedInt($data, 0);
        $setByte = $this->transform->asciiToUnsignedInt($data, 1);
        $pwmByte = $this->transform->asciiToUnsignedInt($data, 2);
        $addOrSub = 0;

        if ($setByte & 1) {
            $addOrSub = 1;
        } elseif (($pwmByte >> 1) & 1) {
            $addOrSub = -1;
        }

        $value = (($setByte >> 2) & 1);

        return [
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_INPUT_PORT_VALUE => ($inputValueAndOutputPortByte >> 7),
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT => ($inputValueAndOutputPortByte & 127),
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_VALUE => $value,
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_PWM => $value ? 0 : $pwmByte,
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_BLINK => (($setByte >> 3) & 7),
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_FADE_IN => $value ? $pwmByte : 0,
            IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_ADD_OR_SUB => $addOrSub,
        ];
    }

    public function getDirectConnectAsString(
        int $inputPort,
        int $inputValue,
        int $outputPort,
        int $value,
        int $pwm,
        int $blink,
        int $fadeIn,
        int $addOrSub,
        int $order = null
    ): string {
        $return = chr($inputPort);

        if (null !== $order) {
            $return .= chr($order);
        }

        if ($value === 1) {
            $pwm = 0;

            if ($addOrSub === 0) {
                $pwm = $fadeIn;
            }
        }

        $return .= chr(($inputValue << 7) | ($outputPort & 127));
        $setByte = ($value << 2) | ($blink << 3);

        if ($addOrSub === -1) {
            $setByte += 64;
        } elseif ($addOrSub === 1) {
            $setByte += 128;
        } else {
            $setByte += 192;
        }

        $return .= chr($setByte) . chr($pwm);

        return $return;
    }
}
