<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Attribute;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Attribute;
use mysqlDatabase;

class Value extends AbstractModel
{
    /**
     * @var int
     */
    private $attributeId;

    /**
     * @var int
     */
    private $order;

    /**
     * @var string
     */
    private $value;

    /**
     * @var Attribute
     */
    private $attribute;

    /**
     * @param mysqlDatabase|null $database
     */
    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->attribute = new Attribute();
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_attribute_value';
    }

    /**
     * @return int
     */
    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    /**
     * @param int $attributeId
     *
     * @return Value
     */
    public function setAttributeId(int $attributeId): Value
    {
        $this->attributeId = $attributeId;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     *
     * @return Value
     */
    public function setOrder(int $order): Value
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Value
     */
    public function setValue(string $value): Value
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->attribute;
    }

    /**
     * @param Attribute $attribute
     *
     * @return Value
     */
    public function setAttribute(Attribute $attribute): Value
    {
        $this->attribute = $attribute;
        $this->setAttributeId($attribute->getId());

        return $this;
    }

    /**
     * @throws SelectError
     *
     * @return Value
     */
    public function loadAttribute(): Value
    {
        $this->loadForeignRecord($this->getAttribute(), $this->getAttributeId());

        return $this;
    }
}
