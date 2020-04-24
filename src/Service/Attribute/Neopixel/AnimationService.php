<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository as ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository as AttributeRepository;

class AnimationService
{
    private const ATTRIBUTE_TYPE = 'animation';

    private const ATTRIBUTE_KEY_PID = 'pid';

    private const ATTRIBUTE_KEY_STARTED = 'started';

    private const ATTRIBUTE_KEY_STEPS = 'steps';

    private const ATTRIBUTE_KEY_TRANSMITTED = 'transmitted';

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
     * @throws Exception
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
     * @throws Exception
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

    public function getSteps(Module $slave): array
    {
        try {
            $steps = [];
            $values = $this->getValueModels($slave, self::ATTRIBUTE_KEY_STARTED);

            foreach ($values as $value) {
                $steps[$value->getOrder()] = JsonUtility::decode($value->getValue());
            }

            return $steps;
        } catch (SelectError $e) {
            return [];
        }
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    public function setSteps(Module $slave, array $steps, bool $transmitted): void
    {
        $this->attributeRepository->startTransaction();

        $stepsAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_STEPS);
        $transmittedAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_TRANSMITTED);

        $this->saveAttributes($stepsAttribute, array_map(function ($step) {
            return JsonUtility::encode($step);
        }, $steps));
        $this->saveAttributes($transmittedAttribute, [$transmitted ? 'true' : 'false']);

        $this->attributeRepository->commit();
    }

    /**
     * @throws DateTimeError
     * @throws SaveError
     */
    public function setPid(Module $slave, int $pid = null): void
    {
        $this->attributeRepository->startTransaction();

        $pidAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_PID);
        $startedAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_STARTED);

        $this->saveAttributes($pidAttribute, [(string) ($pid ?? '')]);
        $this->saveAttributes(
            $startedAttribute,
            [empty($pid) ? '' : (string) ((int) (microtime(true) * 1000000))]
        );

        $this->attributeRepository->commit();
    }

    /**
     * @throws Exception
     * @throws SelectError
     */
    private function getValueModel(Module $slave, string $key): Attribute\Value
    {
        $valueModels = $this->getValueModels($slave, $key);

        if (empty($valueModels)) {
            throw new SelectError(sprintf('Atrribut Wert für "%s" nicht gefunden.', $key));
        }

        return reset($valueModels);
    }

    /**
     * @throws Exception
     *
     * @return Attribute\Value[]
     */
    private function getValueModels(Module $slave, string $key): array
    {
        $valueModels = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            null,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE,
            $key
        );

        return $valueModels;
    }

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
     * @throws SelectError
     */
    private function getAttribute(Module $slave, string $key): Attribute
    {
        $attributes = $this->attributeRepository->getByModule(
            $slave,
            null,
            $key,
            self::ATTRIBUTE_TYPE
        );

        if (count($attributes)) {
            return reset($attributes);
        }

        return $this->newAttribute($slave, $key);
    }

    /**
     * @param string[] $values
     *
     * @throws DateTimeError
     * @throws SaveError
     */
    private function saveAttributes(Attribute $attribute, array $values): void
    {
        $attribute->save();

        foreach ($values as $index => $value) {
            $attributeValue = (new Attribute\Value())
                ->setAttribute($attribute)
                ->setOrder($index)
                ->setValue($value)
            ;
            $attributeValue->save();
        }
    }
}
