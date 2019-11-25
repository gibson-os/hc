<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Attribute;

use GibsonOS\Core\Exception\DateTimeError;
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

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->attribute = new Attribute();
    }

    public static function getTableName(): string
    {
        return 'hc_attribute_value';
    }

    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    public function setAttributeId(int $attributeId): Value
    {
        $this->attributeId = $attributeId;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): Value
    {
        $this->order = $order;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Value
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getAttribute(): Attribute
    {
        $this->loadForeignRecord($this->attribute, $this->getAttributeId());

        return $this->attribute;
    }

    public function setAttribute(Attribute $attribute): Value
    {
        $this->attribute = $attribute;
        $this->setAttributeId((int) $attribute->getId());

        return $this;
    }
}
