<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse;

use GibsonOS\Core\Attribute\GetServices;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use GibsonOS\Module\Hc\Repository\Warehouse\BoxRepository;
use GibsonOS\Module\Hc\Service\Warehouse\Label\AbstractElementService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;
use TCPDF;

class LabelService
{
    /**
     * @param AbstractElementService[] $elementServices
     */
    public function __construct(
        private readonly BoxRepository $boxRepository,
        #[GetServices(['hc/src/Service/Warehouse/Label'], AbstractElementService::class)]
        private readonly array $elementServices,
    ) {
    }

    /**
     * @param mixed $rowOffset
     *
     * @throws SelectError
     */
    public function generate(Module $module, Label $label, int $columnOffset = 0, int $rowOffset = 0): TCPDF
    {
        $pdf = new TCPDF();
        $pdf->setCreator('Gibson OS');
        $pdf->setAuthor('Gibson OS');
        $pdf->setTitle(sprintf('Box Labels für %s', $module->getName()));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setDocCreationTimestamp(time());
        $pdf->setMargins(0, 0);

        $this->generateLabels($pdf, $module, $label, $columnOffset, $rowOffset);

        return $pdf;
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    private function generateLabels(TCPDF $pdf, Module $module, Label $label, int $columnOffset, int $rowOffset): void
    {
        $template = $label->getTemplate();
        $pdf->startPage('P', [$template->getPageWidth(), $template->getPageHeight()]);
        $pdf->setAutoPageBreak(true);
        $pdf->setMargins($template->getMarginLeft(), $template->getMarginTop());
        $row = $rowOffset;
        $column = $columnOffset;

        foreach ($this->boxRepository->getByModule($module) as $box) {
            foreach ($label->getElements() as $element) {
                $top =
                    $template->getMarginTop() + $element->getTop() +
                    ($row * $template->getItemHeight()) + ($row * $template->getItemMarginBottom())
                ;
                $left =
                    $template->getMarginLeft() + $element->getLeft() +
                    ($column * $template->getItemWidth()) +
                    ($column * $template->getItemMarginRight())
                ;

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
