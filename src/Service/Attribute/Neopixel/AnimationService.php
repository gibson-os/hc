<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;

class AnimationService
{
    private const ATTRIBUTE_TYPE = 'animation';

    private const ATTRIBUTE_KEY_PID = 'pid';

    private const ATTRIBUTE_KEY_STARTED = 'started';

    /**
     * @var ValueRepository
     */
    private $valueRepository;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    public function __construct(
        AttributeRepository $attributeRepository,
        ValueRepository $valueRepository
    ) {
        $this->valueRepository = $valueRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param Module $slave
     *
     * @throws Exception
     *
     * @return int|null
     */
    public function getPid(Module $slave): ?int
    {
        try {
            $value = $this->getValueModel($slave, self::ATTRIBUTE_KEY_PID)->getValue();

            return $value === '' ? null : (int) $value;
        } catch (SelectError $e) {
            return null;
        }
    }

    /**
     * @param Module $slave
     *
     * @throws Exception
     *
     * @return int|null
     */
    public function getStarted(Module $slave): ?int
    {
        try {
            $value = $this->getValueModel($slave, self::ATTRIBUTE_KEY_STARTED)->getValue();

            return $value === '' ? null : (int) $value;
        } catch (SelectError $e) {
            return null;
        }
    }

    /**
     * @param Module   $slave
     * @param int|null $pid
     *
     * @throws DateTimeError
     * @throws SaveError
     */
    public function set(Module $slave, int $pid = null): void
    {
        $pidAttribute = $this->newAttribute($slave, self::ATTRIBUTE_KEY_PID);
        $startedAttribute = $this->newAttribute($slave, self::ATTRIBUTE_KEY_STARTED);

        $this->attributeRepository->startTransaction();

        try {
            $attributes = $this->attributeRepository->getByModule($slave, null, null, self::ATTRIBUTE_TYPE);

            foreach ($attributes as $attribute) {
                switch ($attribute->getKey()) {
                    case self::ATTRIBUTE_KEY_PID:
                        $pidAttribute = $attribute;

                        break;
                    case self::ATTRIBUTE_KEY_STARTED:
                        $startedAttribute = $attribute;

                        break;
                }
            }
        } catch (Exception $e) {
        }

        $this->saveAttribute($pidAttribute, (string) ($pid ?? ''));
        $this->saveAttribute($startedAttribute, empty($pid) ? '' : (string) ((int) (microtime(true) * 1000000)));

        $this->attributeRepository->commit();
    }

    /**
     * @param Module $slave
     * @param string $key
     *
     * @throws Exception
     * @throws SelectError
     *
     * @return Attribute\Value
     */
    private function getValueModel(Module $slave, string $key): Attribute\Value
    {
        $valueModels = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            null,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE,
            $key
        );

        if (empty($valueModels)) {
            throw new SelectError(sprintf('Atrribut Wert für "%s" nicht gefunden.', $key));
        }

        return reset($valueModels);
    }

    /**
     * @param Module $slave
     * @param string $key
     *
     * @return Attribute
     */
    private function newAttribute(Module $slave, string $key): Attribute
    {
        return (new Attribute())
            ->setTypeId($slave->getTypeId())
            ->setModuleId($slave->getId())
            ->setType(self::ATTRIBUTE_TYPE)
            ->setKey($key)
        ;
    }

    /**
     * @param Attribute $attribute
     * @param string    $value
     *
     * @throws DateTimeError
     * @throws SaveError
     */
    private function saveAttribute(Attribute $attribute, string $value): void
    {
        $attribute->save();

        $attributeValue = (new Attribute\Value())
            ->setAttribute($attribute)
            ->setOrder(0)
            ->setValue($value)
        ;
        $attributeValue->save();
    }
}
