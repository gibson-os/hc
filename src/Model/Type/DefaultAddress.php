<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Type;

use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Type;
use mysqlDatabase;

class DefaultAddress extends AbstractModel
{
    private int $typeId;

    private int $address;

    private Type $type;

    public function __construct(mysqlDatabase $database = null)
    {
        parent::__construct($database);

        $this->type = new Type();
    }

    public static function getTableName(): string
    {
        return 'hc_type_default_address';
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function setTypeId(int $typeId): DefaultAddress
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function setAddress(int $address): DefaultAddress
    {
        $this->address = $address;

        return $this;
    }

    public function getType(): Type
    {
        $this->loadForeignRecord($this->type, $this->getTypeId());

        return $this->type;
    }

    public function setType(Type $type): DefaultAddress
    {
        $this->type = $type;
        $this->setTypeId((int) $type->getId());

        return $this;
    }
}
