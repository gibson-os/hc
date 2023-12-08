<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse\Label;

use GibsonOS\Core\Attribute\GetEnv;
use GibsonOS\Module\Hc\Enum\Warehouse\Label\Element\Type;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;
use TCPDF;

class UuidElementService extends AbstractElementService
{
    private const OPTION_STRING = 'string';

    public function __construct(
        #[GetEnv('WEB_URL')]
        private readonly string $webUrl
    ) {
    }

    public function getType(): Type
    {
        return Type::UUID;
    }

    public function addElement(TCPDF $pdf, Element $element, Box $box, float $top, float $left): void
    {
        ($element->getOptions()[self::OPTION_STRING] ?? false)
            ? $this->addTextElement(
                $pdf,
                $element,
                $top,
                $left,
                $box->getUuid()
            )
            : $pdf->write2DBarcode(
                $this->webUrl . '/hc/warehouse/box/uuid/' . $box->getUuid(),
                'QRCODE',
                $left,
                $top,
                $element->getWidth(),
                $element->getHeight(),
            )
        ;
    }
}
