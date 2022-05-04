<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Sequence\Neopixel;

use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\CommandService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Command\Neopixel\PlayAnimationCommand;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use JsonException;
use ReflectionException;

class AnimationService
{
    public const SEQUENCE_TYPE = 1;

    public function __construct(
        private SequenceRepository $sequenceRepository,
        private ElementRepository $elementRepository,
        private CommandService $commandService,
        private LedMapper $ledMapper,
        private AnimationAttributeService $animationAttributesService,
        private ModelManager $modelManager
    ) {
    }

    /**
     * @throws SelectError
     */
    public function getByName(Module $slave, string $name): Sequence
    {
        return $this->sequenceRepository->getByName($slave, $name, self::SEQUENCE_TYPE);
    }

    /**
     * @throws SelectError
     * @throws JsonException
     */
    public function getById(int $id): array
    {
        $sequence = $this->sequenceRepository->getById($id);
        $steps = [];

        foreach ($sequence->getElements() as $element) {
            // $steps[$element->getOrder()] = Json::decode($element->getData());
            $steps[] = JsonUtility::decode($element->getData());
        }

        return $steps;
    }

    /**
     * @throws DeleteError
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function save(Module $slave, string $name, array $steps, int $id = null): Sequence
    {
        $this->sequenceRepository->startTransaction();

        $sequence = (new Sequence())
            ->setName($name)
            ->setTypeModel($slave->getType())
            ->setModule($slave)
            ->setType(self::SEQUENCE_TYPE)
        ;

        if (!empty($id)) {
            $sequence->setId($id);

            try {
                $this->elementRepository->deleteBySequence($sequence);
            } catch (DeleteError $e) {
                $this->sequenceRepository->rollback();

                throw $e;
            }
        }

        try {
            $this->modelManager->save($sequence);
        } catch (SaveError $e) {
            $this->sequenceRepository->rollback();

            throw $e;
        }

        foreach ($steps as $order => $step) {
            $sequenceElement = (new Sequence\Element())
                ->setSequence($sequence)
                ->setOrder($order)
                ->setData(JsonUtility::encode($step))
            ;

            try {
                $this->modelManager->save($sequenceElement);
            } catch (SaveError $e) {
                $this->sequenceRepository->rollback();

                throw $e;
            }

            $sequence->addElement($sequenceElement);
        }

        $this->sequenceRepository->commit();

        return $sequence;
    }

    public function play(Module $slave, int $iterations): void
    {
        $this->commandService->executeAsync(PlayAnimationCommand::class, [
            'slaveId' => $slave->getId(),
            'iterations' => $iterations,
        ]);
    }

    public function stop(Module $module): void
    {
        $pid = $this->animationAttributesService->getPid($module);

        if (empty($pid)) {
            return;
        }

        exec('kill -9 ' . $pid);
    }

    /**
     * @return array<int, Led[]>
     */
    public function transformToTimeSteps(Module $module, array $items): array
    {
        $times = [];

        foreach ($items as $item) {
            if (!isset($times[$item['time']])) {
                $times[$item['time']] = [];
            }

            $times[$item['time']][] = $this->ledMapper->mapFromArray($module, $item, true, true);
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
