<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Module\Hc\Dto\Warehouse\Label\ElementMatrix;
use GibsonOS\Module\Hc\Dto\Warehouse\Label\ElementType;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;
use TCPDF;

abstract class AbstractElementService
{
    protected const OPTION_SIZE = 'size';

    abstract public function getType(): ElementType;

    abstract public function addElement(TCPDF $pdf, Element $element, Box $box, float $top, float $left): void;

    protected function addTextElement(TCPDF $pdf, Element $element, float $top, float $left, string $text): void
    {
        $options = $element->getOptions();
        $pdf->setFont('dejavusans', '', $options[self::OPTION_SIZE] ?? 10, '', true);
        $this->setColor($pdf, $element);
        $this->setBackgroundColor($pdf, $element);

        $pdf->MultiCell(
            $element->getWidth(),
            $element->getHeight(),
            $text,
            align: 'L',
            fill: $element->getBackgroundColor() !== null,
            x: $left,
            y: $top,
            fitcell: true,
        );
    }

    protected function setColor(TCPDF $pdf, Element $element): void
    {
        $color = hexdec($element->getColor() ?? '000000');
        $pdf->setColor('text', $color >> 16, ($color >> 8) & 255, $color & 255);
    }

    protected function setBackgroundColor(TCPDF $pdf, Element $element): void
    {
        $backgroundColor = $element->getBackgroundColor();

        if ($backgroundColor === null) {
            return;
        }

        $backgroundColor = hexdec($backgroundColor);
        $pdf->setColor('fill', $backgroundColor >> 16, ($backgroundColor >> 8) & 255, $backgroundColor & 255);
    }

    protected function calcElementMatrix(int $elementCount, float $width, float $height): ElementMatrix
    {
        $sqrt = sqrt($elementCount);
        $columns = (int) ceil($sqrt);
        $rows = (int) round($sqrt);

        if ($width >= $height * 2) {
            $multiplication = (int) ceil($width / $height);
            $columns *= $multiplication;
            $rows -= $multiplication;
        } elseif ($height >= $width * 2) {
            $multiplication = (int) ceil($height / $width);
            $rows *= $multiplication;
            $columns -= $multiplication;
        }

        $columns = max($columns, 1);
        $rows = max($rows, 1);

        if ($columns > $elementCount) {
            $columns = $elementCount;
        }

        return new ElementMatrix(
            $width / $columns,
            $height / $rows,
            $columns
        );
    }
}
