<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Module\Hc\Dto\Warehouse\Label\Element\Type;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;

class LinksElementService extends AbstractElementService
{
    private const OPTION_SEPARATOR = 'separator';

    public function getType(): Type
    {
        return Type::LINKS;
    }

    public function addElement(\TCPDF $pdf, Element $element, Box $box, float $top, float $left): void
    {
        $this->addTextElement(
            $pdf,
            $element,
            $top,
            $left,
            $this->getLinks($element, $box),
        );
    }

    private function getLinks(Element $element, Box $box): string
    {
        $links = [];

        foreach ($box->getItems() as $item) {
            foreach ($item->getLinks() as $link) {
                $links[$link->getUrl()] = $link->getUrl();
            }
        }

        sort($links);

        return implode($element->getOptions()[self::OPTION_SEPARATOR] ?? ', ', $links);
    }
}