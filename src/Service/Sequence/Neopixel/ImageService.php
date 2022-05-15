<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Sequence\Neopixel;

use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\Sequence\ElementRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use JsonException;
use ReflectionException;

class ImageService
{
    public const SEQUENCE_TYPE = 0;

    public function __construct(
        private SequenceRepository $sequenceRepository,
        private ElementRepository $elementRepository,
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
     * @param Led[] $leds
     *
     * @throws DeleteError
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
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
            $this->modelManager->save($sequence);
        } catch (SaveError $e) {
            $this->sequenceRepository->rollback();

            throw $e;
        }

        $sequenceElement = (new Sequence\Element())
            ->setSequence($sequence)
            ->setData(JsonUtility::encode($leds))
        ;

        try {
            $this->modelManager->save($sequenceElement);
        } catch (SaveError $e) {
            $this->sequenceRepository->rollback();

            throw $e;
        }

        $this->sequenceRepository->commit();

        $sequence->addElement($sequenceElement);

        return $sequence;
    }
}
