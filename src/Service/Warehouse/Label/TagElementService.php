<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Module\Hc\Dto\Warehouse\Label\ElementType;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;
use GibsonOS\Module\Hc\Model\Warehouse\Tag;
use TCPDF;

class TagElementService extends AbstractElementService
{
    private const OPTION_SEPARATOR = 'separator';

    public function getType(): ElementType
    {
        return ElementType::TAG;
    }

    public function addElement(TCPDF $pdf, Element $element, Box $box, float $top, float $left): void
    {
        $this->addTextElement(
            $pdf,
            $element,
            $top,
            $left,
            $this->getTags($element, $box),
        );
    }

    private function getTags(Element $element, Box $box): string
    {
        $tags = [];

        foreach ($box->getItems() as $item) {
            foreach ($item->getTags() as $tag) {
                $tags[$tag->getTag()->getId() ?? 0] = $tag->getTag();
            }
        }

        $tags = array_map(fn (Tag $tag): string => $tag->getName(), $tags);
        sort($tags);

        return implode($element->getOptions()[self::OPTION_SEPARATOR] ?? ', ', $tags);
    }
}
