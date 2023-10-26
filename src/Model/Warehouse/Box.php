<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Led;
use JsonSerializable;
use MDO\Enum\OrderDirection;

/**
 * @method Module getModule()
 * @method Box    setModule()
 * @method Led[]  getLeds()
 * @method Box    addLeds(Led[] $leds)
 * @method Box    setLeds(Led[] $leds)
 * @method Item[] getItems()
 * @method Box    addItems(Item[] $leds)
 * @method Box    setItems(Item[] $leds)
 */
#[Table]
class Box extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Column(type: Column::TYPE_VARCHAR, length: 8)]
    #[Key(true)]
    private string $uuid;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $width = 1;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $height = 1;

    #[Column]
    private bool $shown = false;

    #[Constraint]
    protected Module $module;

    #[Constraint('box', Led::class)]
    protected array $leds = [];

    #[Constraint('box', Item::class, orderBy: ['`id`' => OrderDirection::ASC])]
    protected array $items = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Box
    {
        $this->id = $id;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Box
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): Box
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Box
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Box
    {
        $this->left = $left;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): Box
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): Box
    {
        $this->height = $height;

        return $this;
    }

    public function isShown(): bool
    {
        return $this->shown;
    }

    public function setShown(bool $shown): Box
    {
        $this->shown = $shown;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'left' => $this->getLeft(),
            'top' => $this->getTop(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'uuid' => $this->getUuid(),
            'shown' => $this->isShown(),
            'leds' => $this->getLeds(),
            'items' => $this->getItems(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
