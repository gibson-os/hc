<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Mapper\Bme280Mapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Slave\Bme280Service as Bme280Service;
use GibsonOS\Module\Hc\Service\TransformService;

class Bme280Formatter extends AbstractFormatter
{
    private Bme280Mapper $bme280Mapper;

    public function __construct(TransformService $transform, Bme280Mapper $bme280Mapper)
    {
        parent::__construct($transform);
        $this->bme280Mapper = $bme280Mapper;
    }

    /**
     * @throws DateTimeError
     */
    public function text(Log $log): ?string
    {
        switch ($log->getCommand()) {
            case Bme280Service::COMMAND_MEASURE:
                $config = JsonUtility::decode((string) $log->getModule()->getConfig());
                $measureData = $this->bme280Mapper->measureData($log->getRawData(), $config);

                return
                    'Temperatur: ' . $measureData['temperature'] . ' °C<br/>' .
                    'Luftdruck: ' . $measureData['pressure'] . ' hPa<br/>' .
                    'Luftfeuchtigkeit: ' . $measureData['humidity'] . ' %';
            case Bme280Service::COMMAND_CONTROL_HUMIDITY:
                return 'Luftdruck Konfiguration: ' . $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
            case Bme280Service::COMMAND_CONTROL:
                return 'Konfiguration: ' . $this->transform->asciiToUnsignedInt($log->getRawData(), 0);
            case Bme280Service::COMMAND_CALIBRATION1:
                return 'Kalibrierungdaten 1';
            case Bme280Service::COMMAND_CALIBRATION2:
                return 'Kalibrierungdaten 2';
            case Bme280Service::COMMAND_CALIBRATION3:
                return 'Kalibrierungdaten 3';
        }

        return parent::text($log);
    }
}
