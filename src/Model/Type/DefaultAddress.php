<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Type;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Type;

/**
 * @method Type getType()
 */
#[Table]
class DefaultAddress extends AbstractModel
{
    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED], primary: true)]
    private int $typeId;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED], primary: true)]
    private int $address;

    #[Constraint]
    protected Type $type;

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

    public function setType(Type $type): DefaultAddress
    {
        $this->type = $type;
        $this->setTypeId((int) $type->getId());

        return $this;
    }
}
