<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetModels;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\Response\FileResponse;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use GibsonOS\Module\Hc\Service\Warehouse\LabelService;
use GibsonOS\Module\Hc\Store\Warehouse\LabelStore;
use JsonException;
use ReflectionException;

class WarehouseLabelController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(LabelStore $labelStore): AjaxResponse
    {
        return $this->returnSuccess($labelStore->getList(), $labelStore->getCount());
    }

    #[CheckPermission(Permission::READ)]
    public function elements(#[GetModel] Label $label): AjaxResponse
    {
        return new AjaxResponse([
            'data' => $label->getElements(),
            'template' => $label->getTemplate(),
            'success' => true,
            'failure' => false,
        ]);
    }

    /**
     * @throws SelectError
     */
    #[CheckPermission(Permission::READ)]
    public function generate(
        LabelService $labelService,
        #[GetModel] Label $label,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetModels(Box::class)] array $boxes,
        int $offset = 0,
    ): FileResponse {
        $pdf = $labelService->generate($module, $label, $offset);
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'warehouseLabel' . $module->getId() . '.pdf';
        $pdf->Output($filename, 'F');

        return new FileResponse($this->requestService, $filename);
    }
}
