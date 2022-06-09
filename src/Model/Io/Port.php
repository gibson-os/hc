<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Io;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Dto\Io\Direction;

#[Table]
class Port extends AbstractModel
{
    use PortTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column]
    private Direction $direction = Direction::INPUT;

    #[Column]
    private bool $pullUp = true;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $delay = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Port
    {
        $this->id = $id;

        return $this;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(Direction $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function isPullUp(): bool
    {
        return $this->pullUp;
    }

    public function setPullUp(bool $pullUp): self
    {
        $this->pullUp = $pullUp;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

        return $this;
    }
}
