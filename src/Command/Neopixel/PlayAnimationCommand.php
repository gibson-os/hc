<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command\Neopixel;

use Exception;
use GibsonOS\Core\Attribute\Command\Argument;
use GibsonOS\Core\Attribute\GetEnv;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\ArgumentError;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led as AnimationLed;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Neopixel\AnimationService as AnimationSequenceService;
use GibsonOS\Module\Hc\Service\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use JsonException;
use mysqlDatabase;
use Psr\Log\LoggerInterface;
use ReflectionException;

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
        private readonly NeopixelService $neopixelService,
        private readonly AnimationAttributeService $animationAttributeService,
        private readonly AnimationSequenceService $animationSequenceService,
        private readonly LedService $ledService,
        private readonly ModuleRepository $moduleRepository,
        private readonly mysqlDatabase $mysqlDatabase,
        private readonly ModelManager $modelManager,
        #[GetEnv('MYSQL_DATABASE')] private readonly string $mysqlDatabaseName,
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
                    $newLeds[$led->getLed()->getNumber()] = $led;
                }

                $this->mysqlDatabase->openDB($this->mysqlDatabaseName);
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
     * @param AnimationLed[] $leds
     *
     * @throws Exception
     *
     * @return AnimationLed[]
     */
    private function getChanges(Module $slave, array &$leds): array
    {
        ksort($leds);

        return $this->ledService->getChanges(
            array_map(
                fn (Led $led) => (new AnimationLed())
                    ->setLed($led)
                    ->setRed($led->getRed())
                    ->setGreen($led->getGreen())
                    ->setBlue($led->getBlue())
                    ->setFadeIn($led->getFadeIn())
                    ->setBlink($led->getBlink()),
                $this->ledService->getActualState($slave)
            ),
            $leds
        );
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws WriteException
     * @throws JsonException
     * @throws ReflectionException
     */
    private function writeLeds(
        Module $slave,
        NeopixelService $neopixelService,
        array &$leds,
        array &$changedSlaveLeds
    ): void {
        if (empty($changedSlaveLeds)) {
            return;
        }

        $neopixelService->writeSetLeds($slave, array_intersect_key($leds, $changedSlaveLeds));

        array_walk($changedSlaveLeds, function (AnimationLed $led) {
            $this->modelManager->save($led);
        });

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
}
