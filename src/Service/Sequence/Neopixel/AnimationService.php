<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Sequence\Neopixel;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Service\CommandService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Command\Neopixel\PlayAnimationCommand;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;

class AnimationService extends AbstractService
{
    public const SEQUENCE_TYPE = 1;

    private SequenceRepository $sequenceRepository;

    private ElementRepository $elementRepository;

    private CommandService $commandService;

    public function __construct(
        SequenceRepository $sequenceRepository,
        ElementRepository $elementRepository,
        CommandService $commandService
    ) {
        $this->sequenceRepository = $sequenceRepository;
        $this->elementRepository = $elementRepository;
        $this->commandService = $commandService;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getByName(Module $slave, string $name): Sequence
    {
        return $this->sequenceRepository->getByName($slave, $name, self::SEQUENCE_TYPE);
    }

    /**
     * @throws SelectError
     * @throws DateTimeError
     */
    public function getById(int $id): array
    {
        $sequence = $this->sequenceRepository->getById($id);
        $sequence->loadElements();
        $steps = [];

        foreach ($sequence->getElements() ?? [] as $element) {
            //$steps[$element->getOrder()] = Json::decode($element->getData());
            $steps[] = JsonUtility::decode($element->getData());
        }

        return $steps;
    }

    /**
     * @throws DateTimeError
     * @throws DeleteError
     * @throws SaveError
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
            $sequence->save();
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
                $sequenceElement->save();
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

    public function transformToTimeSteps(array $items): array
    {
        $times = [];

        foreach ($items as $item) {
            if (!isset($times[$item['time']])) {
                $times[$item['time']] = [];
            }

            $times[$item['time']][] = $item;
        }

        ksort($times, SORT_NUMERIC);

        return $times;
    }

    public function getRuntimes(array $timeSteps): array
    {
        $lastTime = null;
        $timeStep = null;
        $runtimes = [];
        ksort($timeSteps, SORT_NUMERIC);

        foreach ($timeSteps as $time => $timeStep) {
            if ($lastTime !== null) {
                $runtimes[$lastTime] = ((int) $time) - $lastTime;
            }

            $lastTime = (int) $time;
        }

        if ($lastTime !== null) {
            $maxLength = 0;

            foreach ($timeStep as $led) {
                if ($led['length'] > $maxLength) {
                    $maxLength = $led['length'];
                }
            }

            $runtimes[$lastTime] = $maxLength;
        }

        return $runtimes;
    }
}
