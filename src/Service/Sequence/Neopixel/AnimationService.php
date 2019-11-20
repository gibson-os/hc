<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Sequence\Neopixel;

use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Utility\Json;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\Sequence as SequenceRepository;

class AnimationService extends AbstractService
{
    public const SEQUENCE_TYPE = 1;

    /**
     * @var Module
     */
    private $slave;

    public function __construct(Module $slave)
    {
        $this->slave = $slave;
    }

    /**
     * @param string $name
     *
     * @throws SelectError
     *
     * @return Sequence
     */
    public function getByName(string $name): Sequence
    {
        return SequenceRepository::getByName($this->slave, $name, self::SEQUENCE_TYPE);
    }

    /**
     * @param int $id
     *
     * @throws SelectError
     *
     * @return array
     */
    public function getById(int $id): array
    {
        $sequence = SequenceRepository::getById($id);
        $sequence->loadElements();
        $steps = [];

        foreach ($sequence->getElements() as $element) {
            //$steps[$element->getOrder()] = Json::decode($element->getData());
            $steps[] = Json::decode($element->getData());
        }

        return $steps;
    }

    /**
     * @param string   $name
     * @param array    $steps
     * @param int|null $id
     *
     * @throws DeleteError
     * @throws SaveError
     * @throws SelectError
     *
     * @return Sequence
     */
    public function save(string $name, array $steps, int $id = null): Sequence
    {
        SequenceRepository::startTransaction();

        $sequence = (new Sequence())
            ->setName($name)
            ->setTypeModel($this->slave->loadType()->getType())
            ->setModule($this->slave)
            ->setType(self::SEQUENCE_TYPE)
        ;

        if (!empty($id)) {
            $sequence->setId($id);

            try {
                SequenceRepository\Element::deleteBySequence($sequence);
            } catch (DeleteError $e) {
                SequenceRepository::rollback();

                throw $e;
            }
        }

        try {
            $sequence->save();
        } catch (SaveError $e) {
            SequenceRepository::rollback();

            throw $e;
        }

        foreach ($steps as $order => $step) {
            $sequenceElement = (new Sequence\Element())
                ->setSequence($sequence)
                ->setOrder($order)
                ->setData(Json::encode($step))
            ;

            try {
                $sequenceElement->save();
            } catch (SaveError $e) {
                SequenceRepository::rollback();

                throw $e;
            }

            $sequence->addElement($sequenceElement);
        }

        SequenceRepository::commit();

        return $sequence;
    }

    /**
     * @param array $steps
     * @param int   $iterations
     */
    public function play(array $steps, int $iterations): void
    {
        $dataFilename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hcNeopixelAnimationData' . mt_rand() . '.json';
        file_put_contents($dataFilename, Json::encode($steps));
        /*errlog(
            'sudo -u www-data /usr/bin/php /home/gibson_os/offline/tools/hc/hcNeopixelAnimation.php ' .
            $this->slave->getId() . ' ' .
            $iterations . ' ' .
            $dataFilename
        );*/
        system(
            '/usr/bin/php /home/gibson_os/offline/tools/hc/hcNeopixelAnimation.php ' .
            $this->slave->getId() . ' ' .
            $iterations . ' ' .
            $dataFilename . ' ' .
            '>/dev/null 2>/dev/null &'
        );
    }

    /**
     * @param array $items
     *
     * @return array
     */
    public function transformToTimeSteps(array $items): array
    {
        $times = [];

        foreach ($items as $item) {
            if (!isset($times[$item['time']])) {
                $times[$item['time']] = [];
            }

            $times[$item['time']][] = $item;
        }

        return $times;
    }
}
