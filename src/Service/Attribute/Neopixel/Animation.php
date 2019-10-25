<?php
namespace GibsonOS\Module\Hc\Service\Attribute\Neopixel;

use Exception;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;

class Animation
{
    private const ATTRIBUTE_TYPE = 'animation';
    private const ATTRIBUTE_KEY_PID = 'pid';
    private const ATTRIBUTE_KEY_STARTED = 'started';

    /**
     * @var ModuleModel
     */
    private $slave;
    /**
     * @var ValueRepository
     */
    private $valueRepository;
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    public function __construct(
        ModuleModel $slave,
        AttributeRepository $attributeRepository,
        ValueRepository $valueRepository
    ) {
        $this->slave = $slave;
        $this->valueRepository = $valueRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return int|null
     */
    public function getPid(): ?int
    {
        try {
            $value = $this->getValueModel(self::ATTRIBUTE_KEY_PID)->getValue();
            return $value === '' ? null : (int)$value;
        } catch (SelectError $e) {
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function getStarted(): ?int
    {
        try {
            $value = $this->getValueModel(self::ATTRIBUTE_KEY_STARTED)->getValue();
            return $value === '' ? null : (int)$value;
        } catch (SelectError $e) {
            return null;
        }
    }

    /**
     * @param int|null $pid
     * @throws SaveError
     */
    public function set(int $pid = null): void
    {
        $pidAttribute = $this->newAttribute(self::ATTRIBUTE_KEY_PID);
        $startedAttribute = $this->newAttribute(self::ATTRIBUTE_KEY_STARTED);

        $this->attributeRepository->startTransaction();

        try {
            $attributes = $this->attributeRepository->getByModule($this->slave, null, null, self::ATTRIBUTE_TYPE);

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

        $this->saveAttribute($pidAttribute, $pid ?? '');
        $this->saveAttribute($startedAttribute, empty($pid) ? '' : (int)(microtime(true) * 1000000));

        $this->attributeRepository->commit();
    }

    /**
     * @param string $key
     * @return Attribute\Value
     * @throws SelectError
     */
    private function getValueModel(string $key): Attribute\Value
    {
        $valueModels = $this->valueRepository->getByTypeId(
            $this->slave->getTypeId(),
            null,
            $this->slave->getId(),
            self::ATTRIBUTE_TYPE,
            $key,
            0
        );

        if (empty($valueModels)) {
            throw new SelectError(sprintf('Atrribut Wert für "%s" nicht gefunden.', $key));
        }

        return reset($valueModels);
    }

    /**
     * @param string $key
     * @return Attribute
     */
    private function newAttribute(string $key): Attribute
    {
        return (new Attribute())
            ->setTypeId($this->slave->getTypeId())
            ->setModuleId($this->slave->getId())
            ->setType(self::ATTRIBUTE_TYPE)
            ->setKey($key)
        ;
    }

    /**
     * @param Attribute $attribute
     * @param $value
     * @throws SaveError
     */
    private function saveAttribute(Attribute $attribute, $value): void
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