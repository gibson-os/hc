<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Module\Hc\Dto\Warehouse\Label\Element\Type;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;
use TCPDF;

class StockElementService extends AbstractElementService
{
    private const OPTION_SEPARATOR = 'separator';

    public function getType(): Type
    {
        return Type::STOCK;
    }

    public function addElement(TCPDF $pdf, Element $element, Box $box, float $top, float $left): void
    {
        $this->addTextElement(
            $pdf,
            $element,
            $top,
            $left,
            implode(
                $element->getOptions()[self::OPTION_SEPARATOR] ?? ', ',
                array_map(fn (Item $item): string => (string) $item->getStock(), $box->getItems())
            )
        );
    }
}
