<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Neopixel;

use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ModelMapper;
use GibsonOS\Core\Service\CommandService;
use GibsonOS\Core\Service\ProcessService;
use GibsonOS\Module\Hc\Command\Neopixel\PlayAnimationCommand;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use JsonException;
use ReflectionException;

class AnimationService
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly AnimationRepository $animationRepository,
        private readonly ProcessService $processService,
        private readonly ModelMapper $modelMapper
    ) {
    }

    public function play(Module $module, int $iterations): void
    {
        $this->commandService->executeAsync(PlayAnimationCommand::class, [
            'slaveId' => $module->getId(),
            'iterations' => $iterations,
        ]);
    }

    public function stop(Module $module): bool
    {
        try {
            $animation = $this->animationRepository->getActive($module);
        } catch (SelectError) {
            return true;
        }

        return $this->processService->kill($animation->getPid() ?? 0);
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
