<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Module\Hc\Dto\Warehouse\Label\ElementType;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;
use GibsonOS\Module\Hc\Service\Warehouse\ItemService;
use TCPDF;

class ImageElementService extends AbstractElementService
{
    public function __construct(private readonly ItemService $itemService)
    {
    }

    public function getType(): ElementType
    {
        return ElementType::IMAGE;
    }

    public function addElement(TCPDF $pdf, Element $element, Box $box, float $top, float $left): void
    {
        $images = $this->getImages($box);
        $imageCount = count($images);

        if ($imageCount === 0) {
            return;
        }

        $elementMatrix = $this->calcElementMatrix($imageCount, $element->getWidth(), $element->getHeight());
        $imageWidth = $elementMatrix->getWidth();
        $imageHeight = $elementMatrix->getHeight();
        $count = 0;

        foreach ($images as $image) {
            $this->setBackgroundColor($pdf, $element);
            $pdf->Image(
                $this->itemService->getFilePath() . $image,
                $left,
                $top,
                $imageWidth,
                $imageHeight,
                align: 'M',
                resize: true,
                fitbox: true,
            );
            $left += $imageWidth;
            ++$count;

            if ($count === $elementMatrix->getColumns()) {
                $count = 0;
                $left = $element->getLeft();
                $top += $imageHeight;
            }
        }
    }

    private function getImages(Box $box): array
    {
        $images = [];

        foreach ($box->getItems() as $item) {
            if ($item->getImage() === null) {
                continue;
            }

            $images[] = $item->getImage();
        }

        return $images;
    }
}
