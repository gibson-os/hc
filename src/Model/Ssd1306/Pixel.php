<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Ssd1306;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Module;

/**
 * @method Module getModule()
 * @method Pixel  setModule(Module $module)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'page', 'column', 'bit'])]
class Pixel extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column]
    private bool $on = false;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $page = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $column = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $bit = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Constraint]
    protected Module $module;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Pixel
    {
        $this->id = $id;

        return $this;
    }

    public function isOn(): bool
    {
        return $this->on;
    }

    public function setOn(bool $on): Pixel
    {
        $this->on = $on;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): Pixel
    {
        $this->page = $page;

        return $this;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function setColumn(int $column): Pixel
    {
        $this->column = $column;

        return $this;
    }

    public function getBit(): int
    {
        return $this->bit;
    }

    public function setBit(int $bit): Pixel
    {
        $this->bit = $bit;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Pixel
    {
        $this->moduleId = $moduleId;

        return $this;
    }
}
