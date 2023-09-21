<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Mapper;

use GibsonOS\Module\Hc\Service\TransformService;

class Bme280Mapper
{
    public function __construct(private TransformService $transformService)
    {
    }

    public function measureData(string $data, array $calibration): array
    {
        $pressureRaw =
            ($this->transformService->asciiToUnsignedInt($data, 0) << 12) |
            ($this->transformService->asciiToUnsignedInt($data, 1) << 4) |
            ($this->transformService->asciiToUnsignedInt($data, 2) >> 4)
        ;
        $temperatureRaw =
            ($this->transformService->asciiToUnsignedInt($data, 3) << 12) |
            ($this->transformService->asciiToUnsignedInt($data, 4) << 4) |
            ($this->transformService->asciiToUnsignedInt($data, 5) >> 4)
        ;
        $humidityRaw =
            ($this->transformService->asciiToUnsignedInt($data, 6) << 8) |
            $this->transformService->asciiToUnsignedInt($data, 7)
        ;

        $var1 = (((($temperatureRaw >> 3) - ($calibration['temperature'][0] << 1)) * $calibration['temperature'][1]) >> 11);
        $var2 = (((((($temperatureRaw >> 4) - $calibration['temperature'][0]) * (($temperatureRaw >> 4) - $calibration['temperature'][0])) >> 12) * $calibration['temperature'][2]) >> 14);
        $temperatureFine = $var1 + $var2;
        $temperature = ((($temperatureFine * 5) + 128) >> 8);

        $var1 = ($temperatureFine / 2) - 64000;
        $var2 = $var1 * $var1 * $calibration['pressure'][5] / 32768;
        $var2 += ($var1 * $calibration['pressure'][4] * 2);
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
            $pressure += (($var1 + $var2 + $calibration['pressure'][6]) / 16);
        }

        $humidity = $temperatureFine - 76800;
        $var1 = $humidityRaw - (($calibration['humidity'][3] * 64) + ($calibration['humidity'][4] / 16384 * $humidity));
        $var2 = $calibration['humidity'][1] / 65536 * (1 + ($calibration['humidity'][5] / 67108864 * $humidity * (1 + ($calibration['humidity'][2] / 67108864 * $humidity))));
        $humidity = $var1 * $var2;
        $humidity *= (1 - ($calibration['humidity'][0] * $humidity / 524288));

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
