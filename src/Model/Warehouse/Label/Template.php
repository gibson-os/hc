<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Label;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;

#[Table]
class Template extends AbstractModel implements \JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 128)]
    #[Key(true)]
    private string $name;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $pageWidth;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $pageHeight;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $rows;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $columns;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $marginTop;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $marginLeft;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $itemWidth;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $itemHeight;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $itemMarginRight;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private float $itemMarginBottom;

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

    public function getPageWidth(): float
    {
        return $this->pageWidth;
    }

    public function setPageWidth(float $pageWidth): Template
    {
        $this->pageWidth = $pageWidth;

        return $this;
    }

    public function getPageHeight(): float
    {
        return $this->pageHeight;
    }

    public function setPageHeight(float $pageHeight): Template
    {
        $this->pageHeight = $pageHeight;

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

    public function getMarginTop(): float
    {
        return $this->marginTop;
    }

    public function setMarginTop(float $marginTop): Template
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    public function getMarginLeft(): float
    {
        return $this->marginLeft;
    }

    public function setMarginLeft(float $marginLeft): Template
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    public function getItemWidth(): float
    {
        return $this->itemWidth;
    }

    public function setItemWidth(float $itemWidth): Template
    {
        $this->itemWidth = $itemWidth;

        return $this;
    }

    public function getItemHeight(): float
    {
        return $this->itemHeight;
    }

    public function setItemHeight(float $itemHeight): Template
    {
        $this->itemHeight = $itemHeight;

        return $this;
    }

    public function getItemMarginRight(): float
    {
        return $this->itemMarginRight;
    }

    public function setItemMarginRight(float $itemMarginRight): Template
    {
        $this->itemMarginRight = $itemMarginRight;

        return $this;
    }

    public function getItemMarginBottom(): float
    {
        return $this->itemMarginBottom;
    }

    public function setItemMarginBottom(float $itemMarginBottom): Template
    {
        $this->itemMarginBottom = $itemMarginBottom;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'pageWidth' => $this->getPageWidth(),
            'pageHeight' => $this->getPageHeight(),
            'rows' => $this->getRows(),
            'columns' => $this->getColumns(),
            'marginTop' => $this->getMarginTop(),
            'marginLeft' => $this->getMarginLeft(),
            'itemWidth' => $this->getItemWidth(),
            'itemHeight' => $this->getItemHeight(),
            'itemMarginRight' => $this->getItemMarginRight(),
            'itemMarginBottom' => $this->getItemMarginBottom(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
