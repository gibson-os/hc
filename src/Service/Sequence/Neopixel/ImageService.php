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

class ImageService extends AbstractService
{
    public const SEQUENCE_TYPE = 0;

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
     * @param string   $name
     * @param array    $leds
     * @param int|null $id
     *
     * @throws SelectError
     * @throws SaveError
     * @throws DeleteError
     *
     * @return Sequence
     */
    public function save(string $name, array $leds, int $id = null): Sequence
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

        $sequenceElement = (new Sequence\Element())
            ->setSequence($sequence)
            ->setData(Json::encode($leds))
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
