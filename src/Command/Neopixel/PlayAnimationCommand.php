<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command\Neopixel;

use Exception;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationSequenceService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use mysqlDatabase;

class PlayAnimationCommand extends AbstractCommand
{
    /**
     * @var NeopixelService
     */
    private $neopixelService;

    /**
     * @var AnimationAttributeService
     */
    private $animationAttributeService;

    /**
     * @var AnimationSequenceService
     */
    private $animationSequenceService;

    /**
     * @var LedService
     */
    private $ledService;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var mysqlDatabase
     */
    private $mysqlDatabase;

    /**
     * @var EnvService
     */
    private $envService;

    public function __construct(
        NeopixelService $neopixelService,
        AnimationAttributeService $animationAttributeService,
        AnimationSequenceService $animationSequenceService,
        LedService $ledService,
        ModuleRepository $moduleRepository,
        mysqlDatabase $mysqlDatabase,
        EnvService $envService
    ) {
        $this->neopixelService = $neopixelService;
        $this->animationAttributeService = $animationAttributeService;
        $this->animationSequenceService = $animationSequenceService;
        $this->ledService = $ledService;
        $this->moduleRepository = $moduleRepository;
        $this->mysqlDatabase = $mysqlDatabase;
        $this->envService = $envService;

        $this->setArgument('slaveId', true);
        $this->setArgument('iterations', false);
    }

    protected function run(): int
    {
        $slaveId = (int) $this->getArgument('slaveId');
        $iterations = (int) ($this->getArgument('iterations') ?? 1);

        $slave = $this->moduleRepository->getById($slaveId);
        $lastPid = $this->animationAttributeService->getPid($slave);

        if (!empty($lastPid)) {
            exec('kill -9 ' . $lastPid);
        }

        $this->animationAttributeService->setPid($slave, getmypid());
        $steps = $this->animationAttributeService->getSteps($slave);
        $runtimes = $this->animationSequenceService->getRuntimes($steps);
        $this->mysqlDatabase->closeDB();

        for ($i = 0; $iterations === 0 || $i < $iterations; ++$i) {
            $length = 0;

            foreach ($steps as $time => $leds) {
                $newLeds = [];

                foreach ($leds as $led) {
                    $newLeds[$led['led']] = $led;
                }

                $time += $runtimes[$time] * 1000;
                $this->sleepToTime($time - 5000000);
                $this->mysqlDatabase->openDB($this->envService->getString('MYSQL_DATABASE'));
                $changedLeds = $this->getChanges($slave, $newLeds);
                $this->sleepToTime($time);
                $this->writeLeds($slave, $this->neopixelService, $newLeds, $changedLeds);
                $this->mysqlDatabase->closeDB();

                foreach ($leds as $led) {
                    if ($led['length'] > $length) {
                        $length = $led['length'];
                    }
                }

                $startTime = $time + ($length * 1000);
                $this->sleepToTime($startTime);
            }
        }

        return 0;
    }

    private function sleepToTime(int $time): void
    {
        $now = (int) (microtime(true) * 1000000);

        if ($time - $now > 1000000) {
            usleep($time - $now - 1000000);
        }

        while ((int) (microtime(true) * 1000000) < $time) {
        }
    }

    /**
     * @throws Exception
     */
    private function getChanges(Module $slave, array &$leds): array
    {
        ksort($leds);
        $changedLeds = $this->ledService->getChanges($this->ledService->getActualState($slave), $leds);

        return $this->ledService->getChangedLedsWithoutIgnoredAttributes($changedLeds);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
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

        foreach ($lastChangedIds as $channel => $lastChangedId) {
            $neopixelService->writeChannel(
                $slave,
                $channel,
                $this->ledService->getNumberById($slave, $lastChangedId) + 1
            );
        }
    }
}
