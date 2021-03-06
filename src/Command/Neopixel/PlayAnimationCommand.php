<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command\Neopixel;

use Exception;
use GibsonOS\Core\Attribute\Command\Argument;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\ArgumentError;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationSequenceService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use JsonException;
use mysqlDatabase;
use Psr\Log\LoggerInterface;

/**
 * @description Play not transferred neopixel animation
 */
class PlayAnimationCommand extends AbstractCommand
{
    #[Argument('Neopixel slave ID')]
    private int $slaveId;

    #[Argument('How often the animation should be repeated. 0 = infinity')]
    private int $iterations = 1;

    public function __construct(
        private NeopixelService $neopixelService,
        private AnimationAttributeService $animationAttributeService,
        private AnimationSequenceService $animationSequenceService,
        private LedService $ledService,
        private ModuleRepository $moduleRepository,
        private mysqlDatabase $mysqlDatabase,
        private EnvService $envService,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws ArgumentError
     * @throws DateTimeError
     * @throws GetError
     * @throws Exception
     */
    protected function run(): int
    {
        $slave = $this->moduleRepository->getById($this->slaveId);
        $this->animationSequenceService->stop($slave);
        $this->animationAttributeService->setPid($slave, getmypid());
        $steps = $this->animationAttributeService->getSteps($slave);
        $runtimes = $this->animationSequenceService->getRuntimes($steps);
        $this->mysqlDatabase->closeDB();
        $startTime = (int) (microtime(true) * 1000000);

        for ($i = 0; $this->iterations === 0 || $i < $this->iterations; ++$i) {
            foreach ($steps as $time => $leds) {
                $newLeds = [];

                foreach ($leds as $led) {
                    $newLeds[$led->getNumber()] = $led;
                }

                $this->mysqlDatabase->openDB($this->envService->getString('MYSQL_DATABASE'));
                $changedLeds = $this->getChanges($slave, $newLeds);
                $startTime += 1000000;
                $this->sleepToTime($startTime);
                $this->writeLeds($slave, $this->neopixelService, $newLeds, $changedLeds);
                $this->mysqlDatabase->closeDB();

                $startTime += ($runtimes[$time] * 1000) - 1000000;
                $this->sleepToTime($startTime);
            }
        }

        return self::SUCCESS;
    }

    private function sleepToTime(int $time): void
    {
        $now = (int) (microtime(true) * 1000000);
        $difference = $time - $now;

        if ($difference > 10000) {
            usleep($difference - 10000);
        }

        while ((int) (microtime(true) * 1000000) < $time) {
            // Wait
        }
    }

    /**
     * @param Led[] $leds
     *
     * @throws Exception
     *
     * @return Led[]
     */
    private function getChanges(Module $slave, array &$leds): array
    {
        ksort($leds);

        return $this->ledService->getChanges($this->ledService->getActualState($slave), $leds);
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws WriteException
     * @throws JsonException
     */
    private function writeLeds(Module $slave, NeopixelService $neopixelService, array &$leds, array &$changedSlaveLeds): void
    {
        if (empty($changedSlaveLeds)) {
            return;
        }

        $neopixelService->writeSetLeds($slave, array_intersect_key($leds, $changedSlaveLeds));
        $this->ledService->saveLeds($slave, $changedSlaveLeds);
        $lastChangedIds = $this->ledService->getLastIds($slave, $changedSlaveLeds);

        if (empty($lastChangedIds)) {
            $lastChangedIds = array_map(function ($count) {
                return $count - 1;
            }, JsonUtility::decode((string) $slave->getConfig())['counts']);
        }

        $neopixelService->writeChannels(
            $slave,
            array_map(
                fn ($lastChangedId) => $this->ledService->getNumberById($slave, $lastChangedId) + 1,
                $lastChangedIds
            )
        );
    }

    public function setSlaveId(int $slaveId): PlayAnimationCommand
    {
        $this->slaveId = $slaveId;

        return $this;
    }

    public function setIterations(int $iterations): PlayAnimationCommand
    {
        $this->iterations = $iterations;

        return $this;
    }
}
