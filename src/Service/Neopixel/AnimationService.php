<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Neopixel;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\CommandService;
use GibsonOS\Core\Service\ProcessService;
use GibsonOS\Module\Hc\Command\Neopixel\PlayAnimationCommand;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class AnimationService
{
    public function __construct(
        private readonly NeopixelService $neopixelService,
        private readonly CommandService $commandService,
        private readonly AnimationRepository $animationRepository,
        private readonly ProcessService $processService,
        private readonly ModelManager $modelManager,
        private readonly LedService $ledService,
    ) {
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
    public function play(Animation $animation, int $iterations): void
    {
        $module = $animation->getModule();
        $this->stop($module);
        $this->commandService->executeAsync(PlayAnimationCommand::class, [
            'moduleId' => $module->getId(),
            'iterations' => $iterations,
        ]);
        $animation->setStarted(true);
        $this->modelManager->saveWithoutChildren($animation);
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     * @throws FactoryError
     * @throws ClientException
     * @throws RecordException
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
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    public function stop(Module $module): bool
    {
        try {
            $startedAnimation = $this->animationRepository->getStarted($module);
            $pid = $startedAnimation->getPid();

            $startedAnimation
                ->setPid(null)
                ->setStarted(false)
                ->setPaused(false)
            ;

            if ($startedAnimation->isTransmitted()) {
                $this->neopixelService->writeSequenceStop($module);
            }

            $this->modelManager->save($startedAnimation);

            if ($pid !== null) {
                return $this->processService->kill($pid);
            }
        } catch (SelectError) {
            return false;
        }

        return true;
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    public function pause(Module $module): void
    {
        $startedAnimation = $this->animationRepository->getStarted($module)
            ->setStarted(false)
            ->setPaused(true)
        ;

        if ($startedAnimation->isTransmitted()) {
            $this->neopixelService->writeSequencePause($module);
        }

        $this->modelManager->save($startedAnimation);
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
    public function send(Animation $animation): void
    {
        $module = $animation->getModule();
        $steps = $this->transformToTimeSteps($animation);
        $runtimes = $this->getRuntimes($steps);
        $msPerStep = 1000 / $module->getPwmSpeed();
        $newLeds = [];

        $this->neopixelService->writeSequenceNew($module);

        foreach ($steps as $time => $leds) {
            $oldLeds = $newLeds;
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

            $changedLeds = $this->ledService->getChanges($oldLeds, $newLeds);
            $pwmSteps = (int) ceil($msPerStep * $runtimes[$time]);

            do {
                $runtime = $pwmSteps;

                if ($runtime > 65535) {
                    $pwmSteps -= 65535;
                    $runtime = 65535;
                }

                $this->neopixelService->writeSequenceAddStep($module, $runtime, $changedLeds);
                $changedLeds = [];
            } while ($runtime === 65535);
        }

        try {
            $transmittedAnimation = $this->animationRepository->getTransmitted($module)
                ->setTransmitted(false)
            ;
            $this->modelManager->save($transmittedAnimation);
        } catch (SelectError) {
            // do nothing
        }

        $animation->setTransmitted(true);
        $this->modelManager->save($animation);
    }

    /**
     * @return array<int, Led[]>
     */
    public function transformToTimeSteps(Animation $animation): array
    {
        $times = [];

        foreach ($animation->getLeds() as $led) {
            $time = $led->getTime();

            if (!isset($times[$time])) {
                $times[$time] = [];
            }

            $times[$time][] = $led;
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
                $runtimes[$lastTime] = $time - $lastTime;
            }

            $lastTime = $time;
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
