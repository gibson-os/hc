<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Label;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;

#[Table]
class Template extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 128)]
    #[Key(true)]
    private string $name;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $paperWidth;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $paperHeight;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $rows;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $columns;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $marginTop;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $marginLeft;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemWidth;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemHeight;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemMarginRight;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $itemMarginBottom;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Template
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Template
    {
        $this->name = $name;

        return $this;
    }

    public function getPaperWidth(): int
    {
        return $this->paperWidth;
    }

    public function setPaperWidth(int $paperWidth): Template
    {
        $this->paperWidth = $paperWidth;

        return $this;
    }

    public function getPaperHeight(): int
    {
        return $this->paperHeight;
    }

    public function setPaperHeight(int $paperHeight): Template
    {
        $this->paperHeight = $paperHeight;

        return $this;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function setRows(int $rows): Template
    {
        $this->rows = $rows;

        return $this;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function setColumns(int $columns): Template
    {
        $this->columns = $columns;

        return $this;
    }

    public function getMarginTop(): int
    {
        return $this->marginTop;
    }

    public function setMarginTop(int $marginTop): Template
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    public function getMarginLeft(): int
    {
        return $this->marginLeft;
    }

    public function setMarginLeft(int $marginLeft): Template
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    public function getItemWidth(): int
    {
        return $this->itemWidth;
    }

    public function setItemWidth(int $itemWidth): Template
    {
        $this->itemWidth = $itemWidth;

        return $this;
    }

    public function getItemHeight(): int
    {
        return $this->itemHeight;
    }

    public function setItemHeight(int $itemHeight): Template
    {
        $this->itemHeight = $itemHeight;

        return $this;
    }

    public function getItemMarginRight(): int
    {
        return $this->itemMarginRight;
    }

    public function setItemMarginRight(int $itemMarginRight): Template
    {
        $this->itemMarginRight = $itemMarginRight;

        return $this;
    }

    public function getItemMarginBottom(): int
    {
        return $this->itemMarginBottom;
    }

    public function setItemMarginBottom(int $itemMarginBottom): Template
    {
        $this->itemMarginBottom = $itemMarginBottom;

        return $this;
    }
}
