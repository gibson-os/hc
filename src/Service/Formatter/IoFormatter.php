<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use Exception;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Repository\Attribute\Value;
use GibsonOS\Module\Hc\Repository\Log as LogRepository;
use GibsonOS\Module\Hc\Service\ServerService;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoService;

class IoFormatter extends AbstractHcFormatter
{
    /**
     * @var int
     */
    private $directConnectReadInputPort;

    /**
     * @throws Exception
     *
     * @return int|string|null
     */
    public function command()
    {
        switch ($this->command) {
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

        if ($this->command < $this->module->getConfig()) {
            $name = Value::getByTypeId(
                $this->module->getTypeId(),
                $this->command,
                [(int) $this->module->getId()],
                IoService::ATTRIBUTE_TYPE_PORT,
                IoService::ATTRIBUTE_PORT_KEY_NAME
            );

            return $name[0]->getValue();
        }

        return parent::command();
    }

    /**
     * @return string|null
     */
    public function text(): ?string
    {
        switch ($this->command) {
            case IoService::COMMAND_CONFIGURATION:
                return 'Port Anzahl: ' . $this->transform->hexToInt($this->data);
            case IoService::COMMAND_DEFRAGMENT_DIRECT_CONNECT:
                return 'Defragmentieren';
            case IoService::COMMAND_READ_DIRECT_CONNECT:
                if ($this->direction == ServerService::DIRECTION_OUTPUT) {
                    return null;
                }

                $lastByte = $this->transform->hexToInt($this->data, 2);

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return 'Kein Port gesetzt';
                }

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return 'Kein Befehl vorhanden';
                }

                return null;
            case IoService::COMMAND_DIRECT_CONNECT_STATUS:
                return $this->transform->hexToInt($this->data, 0) ? 'Aktiv' : 'Inaktiv';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                if ($this->direction == ServerService::DIRECTION_OUTPUT) {
                    return 'Standard gesetzt';
                }

                if ($this->transform->hexToInt($this->data, 0)) {
                    return 'Standard geladen';
                }

                return 'Standard nicht vorhanden';
        }

        return parent::text();
    }

    /**
     * @throws Exception
     *
     * @return string|null
     */
    public function render(): ?string
    {
        switch ($this->command) {
            case IoService::COMMAND_STATUS:
            case IoService::COMMAND_DATA_CHANGED:
                $changedPorts = $this->getChangedPorts();

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
                    $name = Value::getByTypeId(
                        $this->module->getTypeId(),
                        (int) $number,
                        [(int) $this->module->getId()],
                        IoService::ATTRIBUTE_TYPE_PORT,
                        IoService::ATTRIBUTE_PORT_KEY_NAME
                    );
                    $valueNames = Value::getByTypeId(
                        $this->module->getTypeId(),
                        (int) $number,
                        [(int) $this->module->getId()],
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
                $inputPort = $this->transform->hexToInt($this->data, 0);
                $inputName = Value::getByTypeId(
                    $this->module->getTypeId(),
                    $inputPort,
                    [(int) $this->module->getId()],
                    IoService::ATTRIBUTE_TYPE_PORT,
                    IoService::ATTRIBUTE_PORT_KEY_NAME
                );

                return
                    '<table>' .
                        '<tr>' .
                            '<th>Eingangs Port</th>' .
                            '<td>' . $inputName[0]->getValue() . '</td>' .
                        '</tr>' .
                        $this->getDirectConnectTableRows($inputPort, substr($this->transform->hexToAscii($this->data), 1)) .
                    '</table>';
            case IoService::COMMAND_SET_DIRECT_CONNECT:
                $inputPort = $this->transform->hexToInt($this->data, 0);
                $inputName = Value::getByTypeId(
                    $this->module->getTypeId(),
                    $inputPort,
                    [(int) $this->module->getId()],
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
                            '<td>' . $this->transform->hexToInt($this->data, 1) . '</td>' .
                        '</tr>' .
                    $this->getDirectConnectTableRows($inputPort, substr($this->transform->hexToAscii($this->data), 2)) .
                    '</table>';
            case IoService::COMMAND_DELETE_DIRECT_CONNECT:
                $inputPort = $this->transform->hexToInt($this->data, 0);
                $inputName = Value::getByTypeId(
                    $this->module->getTypeId(),
                    $inputPort,
                    [(int) $this->module->getId()],
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
                            '<td>' . $this->transform->hexToInt($this->data, 1) . '</td>' .
                        '</tr>' .
                    '</table>';
            case IoService::COMMAND_RESET_DIRECT_CONNECT:
                $inputPort = $this->transform->hexToInt($this->data, 0);
                $inputName = Value::getByTypeId(
                    $this->module->getTypeId(),
                    $inputPort,
                    [(int) $this->module->getId()],
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
                if ($this->direction == ServerService::DIRECTION_OUTPUT) {
                    self::$directConnectReadInputPort = $this->transform->hexToInt($this->data, 0);
                    $inputName = Value::getByTypeId(
                        $this->module->getTypeId(),
                        self::$directConnectReadInputPort,
                        [(int) $this->module->getId()],
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
                                '<td>' . $this->transform->hexToInt($this->data, 1) . '</td>' .
                            '</tr>' .
                        '</table>';
                }

                $lastByte = $this->transform->hexToInt($this->data, 2);

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_SET) {
                    return null;
                }

                if ($lastByte == IoService::DIRECT_CONNECT_READ_NOT_EXIST) {
                    return null;
                }

                if (self::$directConnectReadInputPort === null) {
                    return null;
                }

                return
                    '<table>' .
                        $this->getDirectConnectTableRows(
                            self::$directConnectReadInputPort,
                            $this->transform->hexToAscii($this->data)
                        ) .
                    '</table>';
            case IoService::COMMAND_STATUS_IN_EEPROM:
                //return 'Status in EEPROM';
        }

        if ($this->command < $this->module->getConfig()) {
            $port = self::getPortAsArray($this->transform->hexToAscii($this->data));
            $valueNames = Value::getByTypeId(
                $this->module->getTypeId(),
                $this->command,
                [(int) $this->module->getId()],
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

        return parent::render();
    }

    /**
     * @return array
     */
    private function getChangedPorts(): array
    {
        $ports = self::getPortsAsArray($this->transform->hexToAscii($this->data), (int) $this->module->getConfig());

        if ($this->logId === null) {
            return $ports;
        }

        try {
            $lastData = LogRepository::getPreviewEntryByModuleId(
                $this->logId,
                (int) $this->module->getId(),
                $this->command,
                $this->type,
                $this->direction
            )->getData();
        } catch (SelectError $e) {
            return $ports;
        }

        $lastPorts = self::getPortsAsArray($this->transform->hexToAscii($lastData), (int) $this->module->getConfig());
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
     * @param int    $inputPort
     * @param string $data
     *
     * @throws Exception
     *
     * @return string
     */
    private function getDirectConnectTableRows(int $inputPort, string $data): string
    {
        $directConnect = self::getDirectConnectAsArray($data);
        $moduleIds = [(int) $this->module->getId()];

        $inputValueNames = Value::getByTypeId(
            $this->module->getTypeId(),
            $inputPort,
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES
        );
        $outputName = Value::getByTypeId(
            $this->module->getTypeId(),
            $directConnect[IoService::ATTRIBUTE_DIRECT_CONNECT_KEY_OUTPUT_PORT],
            $moduleIds,
            IoService::ATTRIBUTE_TYPE_PORT,
            IoService::ATTRIBUTE_PORT_KEY_NAME
        );
        $outputValueNames = Value::getByTypeId(
            $this->module->getTypeId(),
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

    /**
     * @param string $data
     *
     * @return array
     */
    public function getPortAsArray(string $data): array
    {
        $byte1 = $this->transform->asciiToInt($data, 0);
        $byte2 = $this->transform->asciiToInt($data, 1);

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

    /**
     * @param string $data
     * @param int    $portCount
     *
     * @return array
     */
    public function getPortsAsArray(string $data, int $portCount): array
    {
        $byteCount = 0;
        $ports = [];

        for ($i = 0; $i < $portCount; ++$i) {
            $ports[$i] = self::getPortAsArray(substr($data, $byteCount, 2));
            $byteCount += 2;
        }

        return $ports;
    }

    /**
     * @param array $data
     *
     * @return string
     */
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

    /**
     * @param string $data
     *
     * @return array
     */
    public function getDirectConnectAsArray(string $data): array
    {
        $inputValueAndOutputPortByte = $this->transform->asciiToInt($data, 0);
        $setByte = $this->transform->asciiToInt($data, 1);
        $pwmByte = $this->transform->asciiToInt($data, 2);
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

    /**
     * @param int      $inputPort
     * @param int      $inputValue
     * @param int      $outputPort
     * @param int      $value
     * @param int      $pwm
     * @param int      $blink
     * @param int      $fadeIn
     * @param int      $addOrSub
     * @param int|null $order
     *
     * @return string
     */
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
