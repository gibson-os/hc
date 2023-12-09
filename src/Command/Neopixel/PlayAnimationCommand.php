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
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;
use GibsonOS\Module\Hc\Service\Neopixel\AnimationService;
use GibsonOS\Module\Hc\Service\Neopixel\LedService;
use JsonException;
use MDO\Client;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * @description Play not transferred neopixel animation
 */
class PlayAnimationCommand extends AbstractCommand
{
    #[Argument('Neopixel module ID')]
    private int $moduleId;

    #[Argument('How often the animation should be repeated. 0 = infinity')]
    private int $iterations = 1;

    public function __construct(
        private readonly NeopixelService $neopixelService,
        private readonly AnimationService $animationService,
        private readonly AnimationRepository $animationRepository,
        private readonly LedService $ledService,
        private readonly ModuleRepository $moduleRepository,
        private readonly Client $client,
        private readonly ModelManager $modelManager,
        #[GetEnv('MYSQL_HOST')]
        private readonly string $mysqlHost,
        #[GetEnv('MYSQL_USER')]
        private readonly string $mysqlUser,
        #[GetEnv('MYSQL_PASS')]
        private readonly string $mysqlPassword,
        #[GetEnv('MYSQL_DATABASE')]
        private readonly string $mysqlDatabaseName,
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
        $module = $this->moduleRepository->getById($this->moduleId);
        $animation = $this->animationRepository->getStarted($module)
            ->setPid(getmypid())
        ;
        $this->modelManager->save($animation);

        $steps = $this->animationService->transformToTimeSteps($animation);
        $runtimes = $this->animationService->getRuntimes($steps);
        $this->client->close();
        $startTime = (int) (microtime(true) * 1000000);

        for ($i = 0; $this->iterations === 0 || $i < $this->iterations; ++$i) {
            foreach ($steps as $time => $leds) {
                $newLeds = [];

                foreach ($leds as $led) {
                    $newLeds[$led->getLed()->getNumber()] = $led->getLed()
                        ->setRed($led->getRed())
                        ->setGreen($led->getGreen())
                        ->setBlue($led->getBlue())
                        ->setBlink($led->getBlink())
                        ->setFadeIn($led->getFadeIn())
                    ;
                }

                $this->client->connect($this->mysqlHost, $this->mysqlUser, $this->mysqlPassword);
                $this->client->useDatabase($this->mysqlDatabaseName);
                $changedLeds = $this->getChanges($module, $newLeds);
                $startTime += 1000000;
                $this->sleepToTime($startTime);
                $this->writeLeds($module, $this->neopixelService, $newLeds, $changedLeds);
                $this->client->close();

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
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     * @throws FactoryError
     * @throws ClientException
     * @throws RecordException
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

        array_walk($changedSlaveLeds, function (Led $led) {
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

    public function setModuleId(int $moduleId): PlayAnimationCommand
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function setIterations(int $iterations): PlayAnimationCommand
    {
        $this->iterations = $iterations;

        return $this;
    }
}
