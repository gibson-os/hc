<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Sequence\Neopixel;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;

class ImageService extends AbstractService
{
    public const SEQUENCE_TYPE = 0;

    private SequenceRepository $sequenceRepository;

    private ElementRepository $elementRepository;

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
     * @param Led[] $leds
     *
     * @throws DateTimeError
     * @throws DeleteError
     * @throws SaveError
     */
    public function save(Module $slave, string $name, array $leds, int $id = null): Sequence
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

        $sequenceElement = (new Sequence\Element())
            ->setSequence($sequence)
            ->setData(JsonUtility::encode($leds))
        ;

        try {
            $sequenceElement->save();
        } catch (SaveError $e) {
            $this->sequenceRepository->rollback();

            throw $e;
        }

        $this->sequenceRepository->commit();

        $sequence->addElement($sequenceElement);

        return $sequence;
    }
}
