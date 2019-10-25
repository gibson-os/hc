<?php
namespace GibsonOS\Module\Hc\Utility\Formatter;

use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Utility\Transform;

abstract class AbstractHcFormatter extends AbstractFormatter
{
    /**
     * @return int|null|string
     */
    public function command()
    {
        switch ($this->command) {
            case AbstractHcSlave::COMMAND_DEVICE_ID:
                return 'Device ID';
            case AbstractHcSlave::COMMAND_TYPE:
                return 'Type ID';
            case AbstractHcSlave::COMMAND_ADDRESS:
                return 'Adresse';
            case AbstractHcSlave::COMMAND_HERTZ:
                return 'Hertz';
            case AbstractHcSlave::COMMAND_EEPROM_SIZE:
                return 'EEPROM Größe';
            case AbstractHcSlave::COMMAND_EEPROM_FREE:
                return 'EEPROM Frei';
            case AbstractHcSlave::COMMAND_EEPROM_POSITION:
                return 'EEPROM Position';
            case AbstractHcSlave::COMMAND_EEPROM_ERASE:
                return 'EEPROM formatiert';
            case AbstractHcSlave::COMMAND_BUFFER_SIZE:
                return 'Buffer Größe';
            case AbstractHcSlave::COMMAND_LEDS:
                return 'Vorhandene LEDs';
            case AbstractHcSlave::COMMAND_POWER_LED:
                return 'Power LED';
            case AbstractHcSlave::COMMAND_ERROR_LED:
                return 'Error LED';
            case AbstractHcSlave::COMMAND_CONNECT_LED:
                return 'Connect LED';
            case AbstractHcSlave::COMMAND_TRANSCEIVE_LED:
                return 'Transreceive LED';
            case AbstractHcSlave::COMMAND_RECEIVE_LED:
                return 'Receive LED';
            case AbstractHcSlave::COMMAND_CUSTOM_LED:
                return 'Custom LED';
            case AbstractHcSlave::COMMAND_RGB_LED:
                return 'RGB LED';
            case AbstractHcSlave::COMMAND_ALL_LEDS:
                return 'LEDs';
            case AbstractHcSlave::COMMAND_STATUS:
            case AbstractHcSlave::COMMAND_DATA_CHANGED:
                return 'Status';
        }

        return parent::command();
    }

    /**
     * @return null|string
     */
    public function text(): ?string
    {
        switch ($this->command) {
            case AbstractHcSlave::COMMAND_DEVICE_ID:
                return Transform::hexToInt($this->data);
            case AbstractHcSlave::COMMAND_TYPE:
                return Transform::hexToInt($this->data, 0);
            case AbstractHcSlave::COMMAND_ADDRESS:
                return Transform::hexToInt($this->data, 2);
            case AbstractHcSlave::COMMAND_HERTZ:
                $units = ['Hz', 'kHz', 'MHz', 'GHz'];
                $hertz = Transform::hexToInt($this->data);

                for ($i = 0; $hertz > 1000; $hertz /= 1000) {
                    $i++;
                }

                return $hertz . ' ' . $units[$i];
            case AbstractHcSlave::COMMAND_EEPROM_SIZE:
            case AbstractHcSlave::COMMAND_EEPROM_FREE:
            case AbstractHcSlave::COMMAND_EEPROM_POSITION:
            case AbstractHcSlave::COMMAND_BUFFER_SIZE:
                return Transform::hexToInt($this->data) . ' Byte';
            case AbstractHcSlave::COMMAND_EEPROM_ERASE:
                return 'formatiert';
        }

        return parent::text();
    }
}