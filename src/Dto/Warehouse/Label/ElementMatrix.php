<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Warehouse\Label;

class ElementMatrix
{
    public function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly int $columns,
    ) {
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }
}
