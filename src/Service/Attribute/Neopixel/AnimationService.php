<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Neopixel\Led;
use GibsonOS\Module\Hc\Mapper\LedMapper;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use JsonException;
use ReflectionException;

class AnimationService
{
    private const ATTRIBUTE_TYPE = 'animation';

    private const ATTRIBUTE_KEY_PID = 'pid';

    private const ATTRIBUTE_KEY_STARTED = 'started';

    private const ATTRIBUTE_KEY_STEPS = 'steps';

    private const ATTRIBUTE_KEY_TRANSMITTED = 'transmitted';

    public function __construct(
        private AttributeRepository $attributeRepository,
        private ValueRepository $valueRepository,
        private LedMapper $ledMapper,
        private ModelManager $modelManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function getPid(Module $slave): ?int
    {
        try {
            $value = $this->getValueModel($slave, self::ATTRIBUTE_KEY_PID)->getValue();

            return $value === '' ? null : (int) $value;
        } catch (SelectError) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public function isTransmitted(Module $slave): bool
    {
        try {
            $value = $this->getValueModel($slave, self::ATTRIBUTE_KEY_TRANSMITTED)->getValue();

            return $value === 'true';
        } catch (SelectError) {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    public function getStarted(Module $slave): bool
    {
        try {
            $value = $this->getValueModel($slave, self::ATTRIBUTE_KEY_STARTED)->getValue();

            return !($value === '') && $value;
        } catch (SelectError) {
            return false;
        }
    }

    /**
     * @return array<int, Led[]>
     */
    public function getSteps(Module $slave): array
    {
        try {
            $steps = [];
            $values = $this->getValueModels($slave, self::ATTRIBUTE_KEY_STEPS);

            foreach ($values as $value) {
                $steps[$value->getOrder()] = $this->ledMapper->mapFromArrays(
                    JsonUtility::decode($value->getValue()),
                    true,
                    true
                );
            }

            return $steps;
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @throws SaveError
     * @throws SelectError
     * @throws DeleteError
     * @throws Exception
     */
    public function setSteps(Module $slave, array $steps, bool $transmitted): void
    {
        $this->attributeRepository->startTransaction();

        $this->valueRepository->deleteByModule($slave, null, [self::ATTRIBUTE_KEY_STEPS], self::ATTRIBUTE_TYPE);
        $stepsAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_STEPS);
        $transmittedAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_TRANSMITTED);

        $this->saveAttribute($stepsAttribute, array_map(function ($step) {
            return JsonUtility::encode($step);
        }, $steps));
        $this->saveAttribute($transmittedAttribute, [$transmitted ? 'true' : 'false']);

        $this->attributeRepository->commit();
    }

    /**
     * @throws SaveError
     * @throws Exception
     */
    public function setPid(Module $slave, int $pid = null): void
    {
        $this->attributeRepository->startTransaction();

        $pidAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_PID);
        $startedAttribute = $this->getAttribute($slave, self::ATTRIBUTE_KEY_STARTED);

        $this->saveAttribute($pidAttribute, [(string) ($pid ?? '')]);
        $this->saveAttribute(
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
        return $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            null,
            [(int) $slave->getId()],
            self::ATTRIBUTE_TYPE,
            $key
        );
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
     * @throws Exception
     */
    private function getAttribute(Module $slave, string $key): Attribute
    {
        try {
            $attributes = $this->attributeRepository->getByModule(
                $slave,
                null,
                $key,
                self::ATTRIBUTE_TYPE
            );
            $attribute = reset($attributes);

            if ($attribute !== false) {
                return $attribute;
            }
        } catch (SelectError) {
            // do nothing
        }

        return $this->newAttribute($slave, $key);
    }

    /**
     * @param string[] $values
     *
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
     */
    private function saveAttribute(Attribute $attribute, array $values): void
    {
        $this->modelManager->save($attribute);

        foreach ($values as $index => $value) {
            $attributeValue = (new Attribute\Value())
                ->setAttribute($attribute)
                ->setOrder($index)
                ->setValue($value)
            ;
            $this->modelManager->save($attributeValue);
        }
    }
}
