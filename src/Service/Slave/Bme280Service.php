<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Slave;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\Formatter\Bme280Formatter;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;

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
     * @var Bme280Formatter
     */
    private $formatter;

    public function __construct(
        MasterService $master,
        EventService $event,
        TransformService $transform,
        Bme280Formatter $formatter,
        array $attributes = []
    ) {
        parent::__construct($master, $event, $transform, $attributes);
        $this->formatter = $formatter;
    }

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
        $this->init($slave);
        $this->calibrate($slave);

        return $slave;
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws SaveError
     */
    private function init(Module $slave): void
    {
        $this->write($slave, self::COMMAND_CONTROL_HUMIDITY, chr(self::OVERSAMPLE_HUMIDITY));
        $control = (self::OVERSAMPLE_TEMPERATURE << 5) | (self::OVERSAMPLE_PRESSURE << 2) | self::MODE;
        $this->write($slave, self::COMMAND_CONTROL, chr($control));
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     */
    private function calibrate(Module $slave): void
    {
        $config = JsonUtility::decode((string) $slave->getConfig());

        if (!is_array($config)) {
            $config = [];
        }

        $config = array_merge($config, $this->calibrateTemperatureAndPressure($slave));
        $config = array_merge($config, $this->calibrateHumidity($slave));

        $slave->setConfig(JsonUtility::encode($config));
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    public function measure(Module $slave): array
    {
        $this->init($slave);

        $wait = 1.25 + (2.3 * self::OVERSAMPLE_TEMPERATURE) + ((2.3 * self::OVERSAMPLE_PRESSURE) + 0.575) + ((2.3 * self::OVERSAMPLE_HUMIDITY) + 0.575);
        usleep((int) ($wait * 10));

        $config = JsonUtility::decode((string) $slave->getConfig());
        $data = $this->read($slave, self::COMMAND_MEASURE, self::COMMAND_MEASURE_READ_LENGTH);

        return $this->formatter->measureData($data, $config);
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    private function calibrateTemperatureAndPressure(Module $slave): array
    {
        $data = $this->read($slave, self::COMMAND_CALIBRATION1, self::COMMAND_CALIBRATION1_READ_LENGTH);
        $config = [
            'temperature' => [
                ($this->transform->asciiToInt($data, 1) << 8) | $this->transform->asciiToInt($data, 0),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 3) << 8) | $this->transform->asciiToInt($data, 2)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 5) << 8) | $this->transform->asciiToInt($data, 4)),
            ],
            'pressure' => [
                ($this->transform->asciiToInt($data, 7) << 8) | $this->transform->asciiToInt($data, 6),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 9) << 8) | $this->transform->asciiToInt($data, 8)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 11) << 8) | $this->transform->asciiToInt($data, 10)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 13) << 8) | $this->transform->asciiToInt($data, 12)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 15) << 8) | $this->transform->asciiToInt($data, 14)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 17) << 8) | $this->transform->asciiToInt($data, 16)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 19) << 8) | $this->transform->asciiToInt($data, 18)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 21) << 8) | $this->transform->asciiToInt($data, 20)),
                $this->transform->getSignedInt(($this->transform->asciiToInt($data, 23) << 8) | $this->transform->asciiToInt($data, 22)),
            ],
        ];

        return $config;
    }

    /**
     * @param Module $slave
     *
     * @throws AbstractException
     * @throws ReceiveError
     * @throws SaveError
     *
     * @return array
     */
    private function calibrateHumidity(Module $slave): array
    {
        $data = $this->read($slave, self::COMMAND_CALIBRATION2, self::COMMAND_CALIBRATION2_READ_LENGTH);
        $config = ['humidity' => [$this->transform->asciiToInt($data, 0)]];

        $data = $this->read($slave, self::COMMAND_CALIBRATION3, self::COMMAND_CALIBRATION3_READ_LENGTH);
        $config['humidity'][] = $this->transform->getSignedInt(($this->transform->asciiToInt($data, 1) << 8) | $this->transform->asciiToInt($data, 0));
        $config['humidity'][] = $this->transform->asciiToInt($data, 2);
        $config['humidity'][] = ($this->transform->asciiToInt($data, 3, false) << 4) | ($this->transform->asciiToInt($data, 4) & 0x0F);
        $config['humidity'][] = ($this->transform->asciiToInt($data, 5, false) << 4) | (($this->transform->asciiToInt($data, 4) >> 4) & 0x0F);
        $config['humidity'][] = $this->transform->asciiToInt($data, 6, false);

        return $config;
    }
}
