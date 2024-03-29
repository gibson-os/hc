<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Module;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\Bme280Mapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\MasterService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use Psr\Log\LoggerInterface;
use ReflectionException;

class Bme280Service extends AbstractModule
{
    public const COMMAND_CALIBRATION1 = 136;

    public const COMMAND_CALIBRATION1_READ_LENGTH = 24;

    public const COMMAND_CALIBRATION2 = 161;

    public const COMMAND_CALIBRATION2_READ_LENGTH = 1;

    public const COMMAND_CALIBRATION3 = 225;

    public const COMMAND_CALIBRATION3_READ_LENGTH = 7;

    public const COMMAND_CONTROL_HUMIDITY = 242;

    public const COMMAND_CONTROL = 244;

    public const COMMAND_MEASURE = 247;

    public const COMMAND_MEASURE_READ_LENGTH = 8;

    public const OVERSAMPLE_HUMIDITY = 2;

    public const OVERSAMPLE_TEMPERATURE = 2;

    public const OVERSAMPLE_PRESSURE = 2;

    public const MODE = 1;

    public function __construct(
        MasterService $masterService,
        TransformService $transformService,
        LogRepository $logRepository,
        private readonly Bme280Mapper $bme280Mapper,
        LoggerInterface $logger,
        ModelManager $modelManager,
        ModelWrapper $modelWrapper,
    ) {
        parent::__construct($masterService, $transformService, $logRepository, $logger, $modelManager, $modelWrapper);
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    public function handshake(Module $module): Module
    {
        $this->init($module);
        $this->calibrate($module);

        return $module;
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    private function init(Module $slave): void
    {
        $this->write($slave, self::COMMAND_CONTROL_HUMIDITY, chr(self::OVERSAMPLE_HUMIDITY));
        $control = (self::OVERSAMPLE_TEMPERATURE << 5) | (self::OVERSAMPLE_PRESSURE << 2) | self::MODE;
        $this->write($slave, self::COMMAND_CONTROL, chr($control));
    }

    /**
     * @throws AbstractException
     * @throws ReceiveError
     * @throws JsonException
     * @throws ReflectionException
     */
    private function calibrate(Module $slave): void
    {
        $config = JsonUtility::decode($slave->getConfig() ?? 'null');

        if (!is_array($config)) {
            $config = [];
        }

        $config = array_merge($config, $this->calibrateTemperatureAndPressure($slave));
        $config = array_merge($config, $this->calibrateHumidity($slave));

        $slave->setConfig(JsonUtility::encode($config));
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     * @throws GetError
     */
    public function measure(Module $slave): array
    {
        $this->init($slave);

        /** @var positive-int $wait */
        $wait = (int) (
            1.25 +
            (2.3 * self::OVERSAMPLE_TEMPERATURE) +
            ((2.3 * self::OVERSAMPLE_PRESSURE) + 0.575) +
            ((2.3 * self::OVERSAMPLE_HUMIDITY) + 0.575)
        );
        usleep($wait * 10);

        $config = JsonUtility::decode((string) $slave->getConfig());
        $data = $this->read($slave, self::COMMAND_MEASURE, self::COMMAND_MEASURE_READ_LENGTH);

        return $this->bme280Mapper->measureData($data, $config);
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     */
    private function calibrateTemperatureAndPressure(Module $slave): array
    {
        $data = $this->read($slave, self::COMMAND_CALIBRATION1, self::COMMAND_CALIBRATION1_READ_LENGTH);

        return [
            'temperature' => [
                ($this->transformService->asciiToUnsignedInt($data, 1) << 8) | $this->transformService->asciiToUnsignedInt($data, 0),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 3) << 8) | $this->transformService->asciiToUnsignedInt($data, 2)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 5) << 8) | $this->transformService->asciiToUnsignedInt($data, 4)),
            ],
            'pressure' => [
                ($this->transformService->asciiToUnsignedInt($data, 7) << 8) | $this->transformService->asciiToUnsignedInt($data, 6),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 9) << 8) | $this->transformService->asciiToUnsignedInt($data, 8)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 11) << 8) | $this->transformService->asciiToUnsignedInt($data, 10)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 13) << 8) | $this->transformService->asciiToUnsignedInt($data, 12)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 15) << 8) | $this->transformService->asciiToUnsignedInt($data, 14)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 17) << 8) | $this->transformService->asciiToUnsignedInt($data, 16)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 19) << 8) | $this->transformService->asciiToUnsignedInt($data, 18)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 21) << 8) | $this->transformService->asciiToUnsignedInt($data, 20)),
                $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 23) << 8) | $this->transformService->asciiToUnsignedInt($data, 22)),
            ],
        ];
    }

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws ClientException
     * @throws RecordException
     */
    private function calibrateHumidity(Module $slave): array
    {
        $data = $this->read($slave, self::COMMAND_CALIBRATION2, self::COMMAND_CALIBRATION2_READ_LENGTH);
        $config = ['humidity' => [$this->transformService->asciiToUnsignedInt($data, 0)]];

        $data = $this->read($slave, self::COMMAND_CALIBRATION3, self::COMMAND_CALIBRATION3_READ_LENGTH);
        $config['humidity'][] = $this->transformService->getSignedInt(($this->transformService->asciiToUnsignedInt($data, 1) << 8) | $this->transformService->asciiToUnsignedInt($data, 0));
        $config['humidity'][] = $this->transformService->asciiToUnsignedInt($data, 2);
        $config['humidity'][] = ($this->transformService->asciiToSignedInt($data, 3) << 4) | ($this->transformService->asciiToUnsignedInt($data, 4) & 0x0F);
        $config['humidity'][] = ($this->transformService->asciiToSignedInt($data, 5) << 4) | (($this->transformService->asciiToUnsignedInt($data, 4) >> 4) & 0x0F);
        $config['humidity'][] = $this->transformService->asciiToSignedInt($data, 6);

        return $config;
    }
}
