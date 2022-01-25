<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Formatter;

use JsonSerializable;

class Explain implements JsonSerializable
{
    public const COLOR_WHITE = 'white';

    public const COLOR_RED = 'red';

    public const COLOR_GREEN = 'green';

    public const COLOR_BLUE = 'blue';

    public const COLOR_YELLOW = 'yellow';

    public const COLOR_MAGENTA = 'magenta';

    public const COLOR_CYAN = 'cyan';

    public const COLOR_BLACK = 'black';

    private string $color = self::COLOR_WHITE;

    public function __construct(private int $startByte, private int $endByte, private string $description)
    {
    }

    public function getStartByte(): int
    {
        return $this->startByte;
    }

    public function setStartByte(int $startByte): void
    {
        $this->startByte = $startByte;
    }

    public function getEndByte(): int
    {
        return $this->endByte;
    }

    public function setEndByte(int $endByte): void
    {
        $this->endByte = $endByte;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): Explain
    {
        $this->color = $color;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'startByte' => $this->getStartByte(),
            'endByte' => $this->getEndByte(),
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
        ];
    }
}
