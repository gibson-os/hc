<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Type;

use GibsonOS\Core\Model\AbstractModel;

/**
 * Class Type.
 *
 * @package GibsonOS\Module\Hc\Model
 */
class DefaultAddress extends AbstractModel
{
    /**
     * @var int
     */
    private $typeId;

    /**
     * @var int
     */
    private $address;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_type_default_address';
    }

    /**
     * @return int
     */
    public function getTypeId(): int
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     *
     * @return DefaultAddress
     */
    public function setTypeId(int $typeId): DefaultAddress
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAddress(): int
    {
        return $this->address;
    }

    /**
     * @param int $address
     *
     * @return DefaultAddress
     */
    public function setAddress(int $address): DefaultAddress
    {
        $this->address = $address;

        return $this;
    }
}
