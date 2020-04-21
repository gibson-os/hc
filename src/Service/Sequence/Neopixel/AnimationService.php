<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Sequence\Neopixel;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;

class AnimationService extends AbstractService
{
    public const SEQUENCE_TYPE = 1;

    /**
     * @var SequenceRepository
     */
    private $sequenceRepository;

    /**
     * @var ElementRepository
     */
    private $elementRepository;

    public function __construct(SequenceRepository $sequenceRepository, ElementRepository $elementRepository)
    {
        $this->sequenceRepository = $sequenceRepository;
        $this->elementRepository = $elementRepository;
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

        foreach ($sequence->getElements() as $element) {
            //$steps[$element->getOrder()] = Json::decode($element->getData());
            $steps[] = JsonUtility::decode($element->getData());
        }

        return $steps;
    }

    /**
     * @throws DateTimeError
     * @throws DeleteError
     * @throws GetError
     * @throws SaveError
     * @throws SelectError
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

    public function play(Module $slave, array $steps, int $iterations): void
    {
        $dataFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hcNeopixelAnimationData' . mt_rand() . '.json';
        file_put_contents($dataFilename, JsonUtility::encode($steps));
        /*errlog(
            'sudo -u www-data /usr/bin/php /home/gibson_os/offline/tools/hc/hcNeopixelAnimation.php ' .
            $this->slave->getId() . ' ' .
            $iterations . ' ' .
            $dataFilename
        );*/
        system(
            '/usr/bin/php /home/gibson_os/offline/tools/hc/hcNeopixelAnimation.php ' .
            $slave->getId() . ' ' .
            $iterations . ' ' .
            $dataFilename . ' ' .
            '>/dev/null 2>/dev/null &'
        );
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
