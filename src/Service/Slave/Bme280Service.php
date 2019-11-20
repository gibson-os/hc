<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Utility\Json;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Utility\TransformService;

class Bme280Service extends AbstractSlave
{
    const COMMAND_CALIBRATION1 = 136;

    const COMMAND_CALIBRATION1_READ_LENGTH = 24;

    const COMMAND_CALIBRATION2 = 161;

    const COMMAND_CALIBRATION2_READ_LENGTH = 1;

    const COMMAND_CALIBRATION3 = 225;

    const COMMAND_CALIBRATION3_READ_LENGTH = 7;

    const COMMAND_CONTROL_HUMIDITY = 242;

    const COMMAND_CONTROL = 244;

    const COMMAND_MEASURE = 247;

    const COMMAND_MEASURE_READ_LENGTH = 8;

    const OVERSAMPLE_HUMIDITY = 2;

    const OVERSAMPLE_TEMPERATURE = 2;

    const OVERSAMPLE_PRESSURE = 2;

    const MODE = 1;

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     *
     * @return Module
     */
    public function handshake(Module $slave): Module
    {
        $this->init();
        $this->calibrate();

        return $slave;
    }

    /**
     * @throws AbstractException
     */
    private function init(): void
    {
        $this->write(self::COMMAND_CONTROL_HUMIDITY, chr(self::OVERSAMPLE_HUMIDITY));
        $control = (self::OVERSAMPLE_TEMPERATURE << 5) | (self::OVERSAMPLE_PRESSURE << 2) | self::MODE;
        $this->write(self::COMMAND_CONTROL, chr($control));
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     */
    private function calibrate(): void
    {
        $config = Json::decode($this->getModel()->getConfig());

        if (!is_array($config)) {
            $config = [];
        }

        $config = array_merge($config, $this->calibrateTemperatureAndPressure());
        $config = array_merge($config, $this->calibrateHumidity());

        $this->slave->setConfig(Json::encode($config));
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     *
     * @return array
     */
    public function measure(): array
    {
        $this->init();

        $wait = 1.25 + (2.3 * self::OVERSAMPLE_TEMPERATURE) + ((2.3 * self::OVERSAMPLE_PRESSURE) + 0.575) + ((2.3 * self::OVERSAMPLE_HUMIDITY) + 0.575);
        usleep($wait * 10);

        $config = Json::decode($this->slave->getConfig());
        $data = $this->read(self::COMMAND_MEASURE, self::COMMAND_MEASURE_READ_LENGTH);

        return \GibsonOS\Module\Hc\Utility\Formatter\Bme280::measureData($data, $config);
    }

    /**
     * @throws ReceiveError
     * @throws AbstractException
     *
     * @return array
     */
    private function calibrateTemperatureAndPressure(): array
    {
        $data = $this->read(self::COMMAND_CALIBRATION1, self::COMMAND_CALIBRATION1_READ_LENGTH);
        $config = [
            'temperature' => [
                (TransformService::asciiToInt($data, 1) << 8) | TransformService::asciiToInt($data, 0),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 3) << 8) | TransformService::asciiToInt($data, 2)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 5) << 8) | TransformService::asciiToInt($data, 4)),
            ],
            'pressure' => [
                (TransformService::asciiToInt($data, 7) << 8) | TransformService::asciiToInt($data, 6),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 9) << 8) | TransformService::asciiToInt($data, 8)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 11) << 8) | TransformService::asciiToInt($data, 10)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 13) << 8) | TransformService::asciiToInt($data, 12)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 15) << 8) | TransformService::asciiToInt($data, 14)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 17) << 8) | TransformService::asciiToInt($data, 16)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 19) << 8) | TransformService::asciiToInt($data, 18)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 21) << 8) | TransformService::asciiToInt($data, 20)),
                TransformService::getSignedInt((TransformService::asciiToInt($data, 23) << 8) | TransformService::asciiToInt($data, 22)),
            ],
        ];

        return $config;
    }

    /**
     * @throws ReceiveError
     * @throws AbstractException
     *
     * @return array
     */
    private function calibrateHumidity(): array
    {
        $data = $this->read(self::COMMAND_CALIBRATION2, self::COMMAND_CALIBRATION2_READ_LENGTH);
        $config = ['humidity' => [TransformService::asciiToInt($data, 0)]];

        $data = $this->read(self::COMMAND_CALIBRATION3, self::COMMAND_CALIBRATION3_READ_LENGTH);
        $config['humidity'][] = TransformService::getSignedInt((TransformService::asciiToInt($data, 1) << 8) | TransformService::asciiToInt($data, 0));
        $config['humidity'][] = TransformService::asciiToInt($data, 2);
        $config['humidity'][] = (TransformService::asciiToInt($data, 3, false) << 4) | (TransformService::asciiToInt($data, 4) & 0x0F);
        $config['humidity'][] = (TransformService::asciiToInt($data, 5, false) << 4) | ((TransformService::asciiToInt($data, 4) >> 4) & 0x0F);
        $config['humidity'][] = TransformService::asciiToInt($data, 6, false);

        return $config;
    }
}
