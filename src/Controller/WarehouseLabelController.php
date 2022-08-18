<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\FileResponse;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use GibsonOS\Module\Hc\Service\Warehouse\LabelService;

class WarehouseLabelController extends AbstractController
{
    #[CheckPermission(Permission::READ)]
    public function generate(
        LabelService $labelService,
        #[GetModel] Label $label,
        #[GetModel(['id' => 'moduleId'])] Module $module,
    ): FileResponse {
        $pdf = $labelService->generate($module, $label);
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'warehouseLabel' . $module->getId() . '.pdf';
        $pdf->Output($filename, 'F');

        return new FileResponse($this->requestService, $filename);
    }
}
