<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse;

use GibsonOS\Core\Attribute\GetServices;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use GibsonOS\Module\Hc\Repository\Warehouse\BoxRepository;
use GibsonOS\Module\Hc\Service\Warehouse\Label\AbstractElementService;
use TCPDF;

class LabelService
{
    /**
     * @param AbstractElementService[] $elementServices
     */
    public function __construct(
        private readonly BoxRepository $boxRepository,
        #[GetServices(['hc/src/Service/Warehouse/Label'], AbstractElementService::class)] private readonly array $elementServices
    ) {
    }

    /**
     * @throws SelectError
     */
    public function generate(Module $module, Label $label, int $offset = 0): TCPDF
    {
        $pdf = new TCPDF();
        $pdf->setCreator('Gibson OS');
        $pdf->setAuthor('Gibson OS');
        $pdf->setTitle(sprintf('Box Labels für %s', $module->getName()));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setDocCreationTimestamp(time());

        $this->generateLabels($pdf, $module, $label, $offset);

        return $pdf;
    }

    /**
     * @throws SelectError
     */
    private function generateLabels(TCPDF $pdf, Module $module, Label $label, int $offset): void
    {
        $template = $label->getTemplate();
        $pdf->startPage('P', [$template->getPageWidth(), $template->getPageHeight()]);
        $pdf->setAutoPageBreak(true);
        $pdf->setMargins($template->getMarginLeft(), $template->getMarginTop());
        $row = 0;
        $column = 0;

        foreach ($this->boxRepository->getByModule($module) as $box) {
            foreach ($label->getElements() as $element) {
                $top = $element->getTop() + ($row * $template->getItemHeight()) + ($row * $template->getItemMarginBottom());
                $left = $element->getLeft() + ($column * $template->getItemWidth()) + ($column * $template->getItemMarginRight());

                foreach ($this->elementServices as $elementService) {
                    if ($elementService->getType() !== $element->getType()) {
                        continue;
                    }

                    $elementService->addElement($pdf, $element, $box, $top, $left);
                }
            }

            ++$column;

            if ($column === $template->getColumns()) {
                $column = 0;
                ++$row;
            }

            if ($row === $template->getRows()) {
                $pdf->endPage();
                $pdf->startPage('P', [$template->getPageWidth(), $template->getPageHeight()]);
                $row = 0;
            }
        }
    }
}
