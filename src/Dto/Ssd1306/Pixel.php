<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Ssd1306;

class Pixel implements \JsonSerializable
{
    private int $page;

    private int $column;

    private int $bit;

    private bool $on = false;

    public function __construct(int $page, int $column, int $bit)
    {
        $this->page = $page;
        $this->column = $column;
        $this->bit = $bit;
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
    
    public function isOn(): bool
    {
        return $this->on;
    }
    
    public function setOn(bool $on): Pixel
    {
        $this->on = $on;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'page' => $this->getPage(),
            'column' => $this->getColumn(),
            'bit' => $this->getBit(),
            'on' => $this->isOn()
        ];
    }
}