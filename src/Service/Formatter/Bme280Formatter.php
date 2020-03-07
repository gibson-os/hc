<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Slave\Bme280Service as Bme280Service;

class Bme280Formatter extends AbstractFormatter
{
    public function text(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case Bme280Service::COMMAND_MEASURE:
                $config = JsonUtility::decode((string) $log->getModule()->getConfig());
                $measureData = self::measureData($this->transform->hexToAscii($log->getData()), $config);

                return
                    'Temperatur: ' . $measureData['temperature'] . ' °C<br/>' .
                    'Luftdruck: ' . $measureData['pressure'] . ' hPa<br/>' .
                    'Luftfeuchtigkeit: ' . $measureData['humidity'] . ' %';
            case Bme280Service::COMMAND_CONTROL_HUMIDITY:
                return 'Luftdruck Konfiguration: ' . $this->transform->hexToInt($log->getData(), 0);
            case Bme280Service::COMMAND_CONTROL:
                return 'Konfiguration: ' . $this->transform->hexToInt($log->getData(), 0);
            case Bme280Service::COMMAND_CALIBRATION1:
                return 'Kalibrierungdaten 1';
            case Bme280Service::COMMAND_CALIBRATION2:
                return 'Kalibrierungdaten 2';
            case Bme280Service::COMMAND_CALIBRATION3:
                return 'Kalibrierungdaten 3';
        }

        return parent::text($log);
    }

    public function measureData(string $data, array $calibration): array
    {
        $pressureRaw =
            ($this->transform->asciiToUnsignedInt($data, 0) << 12) |
            ($this->transform->asciiToUnsignedInt($data, 1) << 4) |
            ($this->transform->asciiToUnsignedInt($data, 2) >> 4)
        ;
        $temperatureRaw =
            ($this->transform->asciiToUnsignedInt($data, 3) << 12) |
            ($this->transform->asciiToUnsignedInt($data, 4) << 4) |
            ($this->transform->asciiToUnsignedInt($data, 5) >> 4)
        ;
        $humidityRaw =
            ($this->transform->asciiToUnsignedInt($data, 6) << 8) |
            $this->transform->asciiToUnsignedInt($data, 7)
        ;

        $var1 = ((((($temperatureRaw >> 3) - ($calibration['temperature'][0] << 1))) * ($calibration['temperature'][1])) >> 11);
        $var2 = (((((($temperatureRaw >> 4) - ($calibration['temperature'][0])) * (($temperatureRaw >> 4) - ($calibration['temperature'][0]))) >> 12) * ($calibration['temperature'][2])) >> 14);
        $temperatureFine = $var1 + $var2;
        $temperature = ((($temperatureFine * 5) + 128) >> 8);

        $var1 = ($temperatureFine / 2) - 64000;
        $var2 = $var1 * $var1 * $calibration['pressure'][5] / 32768;
        $var2 = $var2 + ($var1 * $calibration['pressure'][4] * 2);
        $var2 = ($var2 / 4) + ($calibration['pressure'][3] * 65536);
        $var1 = (($calibration['pressure'][2] * $var1 * $var1 / 524288) + ($calibration['pressure'][1] * $var1)) / 524288;
        $var1 = (($var1 / 32768) + 1) * $calibration['pressure'][0];

        if ($var1 == 0) {
            $pressure = 0;
        } else {
            $pressure = 1048576 - $pressureRaw;
            $pressure = (($pressure - $var2 / 4096) * 6250) / $var1;
            $var1 = $calibration['pressure'][8] * $pressure * $pressure / 2147483648;
            $var2 = $pressure * $calibration['pressure'][7] / 32768;
            $pressure = $pressure + (($var1 + $var2 + $calibration['pressure'][6]) / 16);
        }

        $humidity = $temperatureFine - 76800;
        $var1 = $humidityRaw - (($calibration['humidity'][3] * 64) + ($calibration['humidity'][4] / 16384 * $humidity));
        $var2 = $calibration['humidity'][1] / 65536 * (1 + ($calibration['humidity'][5] / 67108864 * $humidity * (1 + ($calibration['humidity'][2] / 67108864 * $humidity))));
        $humidity = $var1 * $var2;
        $humidity = $humidity * (1 - ($calibration['humidity'][0] * $humidity / 524288));

        if ($humidity > 100) {
            $humidity = 100;
        } elseif ($humidity < 0) {
            $humidity = 0;
        }

        return [
            'temperature' => $temperature / 100,
            'pressure' => $pressure / 100,
            'humidity' => $humidity,
        ];
    }
}
