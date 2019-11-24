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
use GibsonOS\Module\Hc\Repository\Sequence as SequenceRepository;

class ImageService extends AbstractService
{
    public const SEQUENCE_TYPE = 0;

    /**
     * @throws SelectError
     */
    public function getByName(Module $slave, string $name): Sequence
    {
        return SequenceRepository::getByName($slave, $name, self::SEQUENCE_TYPE);
    }

    /**
     * @throws DateTimeError
     * @throws DeleteError
     * @throws GetError
     * @throws SaveError
     */
    public function save(Module $slave, string $name, array $leds, int $id = null): Sequence
    {
        SequenceRepository::startTransaction();

        $sequence = (new Sequence())
            ->setName($name)
            ->setTypeModel($slave->getType())
            ->setModule($slave)
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

        $sequenceElement = (new Sequence\Element())
            ->setSequence($sequence)
            ->setData(JsonUtility::encode($leds))
        ;

        try {
            $sequenceElement->save();
        } catch (SaveError $e) {
            SequenceRepository::rollback();

            throw $e;
        }

        SequenceRepository::commit();

        $sequence->addElement($sequenceElement);

        return $sequence;
    }
}
