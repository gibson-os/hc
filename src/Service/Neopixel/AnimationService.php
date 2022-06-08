<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Neopixel;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Mapper\ModelMapper;
use GibsonOS\Core\Service\CommandService;
use GibsonOS\Core\Service\ProcessService;
use GibsonOS\Module\Hc\Command\Neopixel\PlayAnimationCommand;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use JsonException;
use ReflectionException;

class AnimationService
{
    public function __construct(
        private readonly NeopixelService $neopixelService,
        private readonly CommandService $commandService,
        private readonly AnimationRepository $animationRepository,
        private readonly ProcessService $processService,
        private readonly ModelMapper $modelMapper,
        private readonly ModelManager $modelManager
    ) {
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    public function play(Animation $animation, int $iterations): void
    {
        $module = $animation->getModule();
        $this->stop($module);
        $this->commandService->executeAsync(PlayAnimationCommand::class, [
            'slaveId' => $module->getId(),
            'iterations' => $iterations,
        ]);
        $animation->setStarted(true);
        $this->modelManager->save($animation);
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    public function start(Module $module, int $iterations): void
    {
        $this->stop($module);
        $animation = $this->animationRepository->getTransmitted($module);
        $this->neopixelService->writeSequenceStart($module, $iterations);
        $animation->setStarted(true);
        $this->modelManager->save($animation);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    public function stop(Module $module): bool
    {
        $startedAnimation = $this->animationRepository->getStarted($module);
        $pid = $startedAnimation->getPid();

        $startedAnimation
            ->setPid(null)
            ->setStarted(false)
        ;

        if ($pid !== null) {
            $this->modelManager->save($startedAnimation);

            return $this->processService->kill($pid);
        }

        if ($startedAnimation->isTransmitted()) {
            $this->neopixelService->writeSequenceStop($module);
            $this->modelManager->save($startedAnimation);

            return true;
        }

        return false;
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws FactoryError
     * @throws MapperException
     *
     * @return array<int, Led[]>
     */
    public function transformToTimeSteps(array $items): array
    {
        $times = [];

        foreach ($items as $item) {
            if (!isset($times[$item['time']])) {
                $times[$item['time']] = [];
            }

            $times[$item['time']][] = $this->modelMapper->mapToObject(Led::class, $item);
        }

        ksort($times, SORT_NUMERIC);

        return $times;
    }

    /**
     * @param array<int, Led[]> $timeSteps
     *
     * @return int[]
     */
    public function getRuntimes(array $timeSteps): array
    {
        $lastTime = null;
        $leds = null;
        $runtimes = [];
        ksort($timeSteps, SORT_NUMERIC);

        foreach ($timeSteps as $time => $leds) {
            if ($lastTime !== null) {
                $runtimes[$lastTime] = ((int) $time) - $lastTime;
            }

            $lastTime = (int) $time;
        }

        if ($lastTime !== null && $leds !== null) {
            $maxLength = 0;

            foreach ($leds as $led) {
                if ($led->getLength() > $maxLength) {
                    $maxLength = $led->getLength();
                }
            }

            $runtimes[$lastTime] = $maxLength;
        }

        return $runtimes;
    }
}
