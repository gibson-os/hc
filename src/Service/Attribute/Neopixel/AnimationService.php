<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Mapper\ModelMapper;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led;
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
        private readonly AttributeRepository $attributeRepository,
        private readonly ValueRepository $valueRepository,
        private readonly ModelManager $modelManager,
        private readonly ModelMapper $modelMapper
    ) {
    }

    /**
     * @return array<int, Led[]>
     */
    public function getSteps(Module $module): array
    {
        try {
            $steps = [];
            $values = $this->getValueModels($module, self::ATTRIBUTE_KEY_STEPS);

            foreach ($values as $value) {
                foreach (JsonUtility::decode($value->getValue()) as $ledData) {
                    $steps[$value->getOrder()][] = $this->modelMapper->mapToObject(
                        Led::class,
                        ['module' => $module, 'leds' => $ledData]
                    );
                }
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
