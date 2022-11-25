<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Module\Hc\Dto\Warehouse\Label\Barcode;
use GibsonOS\Module\Hc\Dto\Warehouse\Label\Element\Type;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;

class CodesElementService extends AbstractElementService
{
    private const OPTION_SEPARATOR = 'separator';

    private const OPTION_STRING = 'string';

    private const OPTION_INPUT_TYPES = 'inputTypes';

    private const OPTION_OUTPUT_TYPE = 'outputType';

    private const OPTION_PADDING = 'padding';

    public function getType(): Type
    {
        return Type::CODES;
    }

    public function addElement(\TCPDF $pdf, Element $element, Box $box, float $top, float $left): void
    {
        $codes = $this->getCodes($element, $box);

        if (count($codes) === 0) {
            return;
        }

        $options = $element->getOptions();

        if ($options[self::OPTION_STRING] ?? false) {
            $this->addTextElement(
                $pdf,
                $element,
                $top,
                $left,
                implode($options[self::OPTION_SEPARATOR] ?? ', ', $codes)
            );

            return;
        }

        $elementMatrix = $this->calcElementMatrix(count($codes), $element->getWidth(), $element->getHeight());
        $codeWidth = $elementMatrix->getWidth();
        $codeHeight = $elementMatrix->getHeight();
        $count = 0;
        $outputType = Barcode::from($options[self::OPTION_OUTPUT_TYPE] ?? Barcode::QRCODE->value);
        $is1d = $outputType->is1d();

        foreach ($codes as $code) {
            $is1d
                ? $pdf->write1DBarcode(
                    $code,
                    $outputType->value,
                    $left,
                    $top,
                    $codeWidth,
                    $codeHeight,
                    style: ['padding' => $options[self::OPTION_PADDING] ?? 0],
                    align: 'M',
                )
                : $pdf->write2DBarcode(
                    $code,
                    $outputType->value,
                    $left,
                    $top,
                    $codeWidth,
                    $codeHeight,
                    style: ['padding' => $options[self::OPTION_PADDING] ?? 0],
                    align: 'M',
                )
            ;
            $left += $codeWidth;
            ++$count;

            if ($count === $elementMatrix->getColumns()) {
                $count = 0;
                $left = $element->getLeft();
                $top += $codeHeight;
            }
        }
    }

    private function getCodes(Element $element, Box $box): array
    {
        $codes = [];
        $options = $element->getOptions();

        foreach ($box->getItems() as $item) {
            foreach ($item->getCodes() as $code) {
                if (
                    count($options[self::OPTION_INPUT_TYPES] ?? []) > 0 &&
                    !in_array($code->getType()->value, $options[self::OPTION_INPUT_TYPES] ?? [])
                ) {
                    continue;
                }

                $codes[$code->getCode()] = $code->getCode();
            }
        }

        sort($codes);

        return $codes;
    }
}
