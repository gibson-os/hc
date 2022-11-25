<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Neopixel\Led as NeopixelLed;
use GibsonOS\Module\Hc\Model\Warehouse\Box;

/**
 * @method Box         getBox()
 * @method Led         setBox(Box $box)
 * @method NeopixelLed getLed()
 * @method Led         setLed(NeopixelLed $led)
 */
#[Table]
class Led extends AbstractModel implements \JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $boxId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $ledId;

    #[Constraint]
    protected Box $box;

    #[Constraint]
    protected NeopixelLed $led;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Led
    {
        $this->id = $id;

        return $this;
    }

    public function getBoxId(): int
    {
        return $this->boxId;
    }

    public function setBoxId(int $boxId): Led
    {
        $this->boxId = $boxId;

        return $this;
    }

    public function getLedId(): int
    {
        return $this->ledId;
    }

    public function setLedId(int $ledId): Led
    {
        $this->ledId = $ledId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'led' => $this->getLed(),
        ];
    }
}
