<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Formatter;

use JsonSerializable;

class Explain implements JsonSerializable
{
    private int $startByte;

    private int $endByte;

    private string $declaration;

    public function __construct(int $startByte, int $endByte, string $declaration)
    {
        $this->startByte = $startByte;
        $this->endByte = $endByte;
        $this->declaration = $declaration;
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

    public function getDeclaration(): string
    {
        return $this->declaration;
    }

    public function setDeclaration(string $declaration): void
    {
        $this->declaration = $declaration;
    }

    public function jsonSerialize(): array
    {
        return [
            'startByte' => $this->getStartByte(),
            'endByte' => $this->getEndByte(),
            'declaration' => $this->getDeclaration(),
        ];
    }
}
