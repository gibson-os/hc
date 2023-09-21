<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetModels;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\Model\DeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\Response\FileResponse;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Label;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Template;
use GibsonOS\Module\Hc\Service\Warehouse\LabelService;
use GibsonOS\Module\Hc\Store\Warehouse\Label\TemplateStore;
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
    #[CheckPermission([Permission::READ])]
    public function get(LabelStore $labelStore): AjaxResponse
    {
        return $this->returnSuccess($labelStore->getList(), $labelStore->getCount());
    }

    #[CheckPermission([Permission::READ])]
    public function getElements(#[GetModel] Label $label): AjaxResponse
    {
        return new AjaxResponse([
            'data' => $label->getElements(),
            'template' => $label->getTemplate(),
            'success' => true,
            'failure' => false,
        ]);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ, Permission::MANAGE])]
    public function getTemplates(TemplateStore $templateStore): AjaxResponse
    {
        return $this->returnSuccess($templateStore->getList(), $templateStore->getCount());
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission([Permission::WRITE])]
    public function post(
        ModelManager $modelManager,
        #[GetMappedModel]
        Label $label,
    ): AjaxResponse {
        $modelManager->save($label);

        return $this->returnSuccess();
    }

    /**
     * @throws JsonException
     * @throws DeleteError
     */
    #[CheckPermission([Permission::DELETE])]
    public function delete(
        ModelManager $modelManager,
        #[GetModels(Label::class)]
        array $labels
    ): AjaxResponse {
        foreach ($labels as $label) {
            $modelManager->delete($label);
        }

        return $this->returnSuccess();
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission([Permission::WRITE, Permission::MANAGE])]
    public function postTemplate(
        ModelManager $modelManager,
        #[GetMappedModel]
        Template $template,
    ): AjaxResponse {
        $modelManager->save($template);

        return $this->returnSuccess();
    }

    /**
     * @throws JsonException
     * @throws DeleteError
     */
    #[CheckPermission([Permission::DELETE, Permission::MANAGE])]
    public function deleteTemplates(
        ModelManager $modelManager,
        #[GetModels(Template::class)]
        array $templates
    ): AjaxResponse {
        foreach ($templates as $template) {
            $modelManager->delete($template);
        }

        return $this->returnSuccess();
    }

    /**
     * @throws SelectError
     */
    #[CheckPermission([Permission::READ])]
    public function getGenerate(
        LabelService $labelService,
        #[GetModel]
        Label $label,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        #[GetModels(Item::class)]
        array $items,
        int $columnOffset = 0,
        int $rowOffset = 0,
    ): FileResponse {
        $pdf = $labelService->generate($module, $label, $columnOffset, $rowOffset);
        $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'warehouseLabel' . $module->getId() . '.pdf';
        $pdf->Output($filename, 'F');

        return new FileResponse($this->requestService, $filename);
    }
}
