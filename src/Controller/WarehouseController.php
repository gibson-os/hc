<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModels;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Store\Warehouse\BoxStore;
use JsonException;
use ReflectionException;

class WarehouseController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        BoxStore $boxStore,
        #[GetModel(['id' => 'moduleId'])] Module $module,
    ): AjaxResponse {
        $boxStore->setModule($module);

        return $this->returnSuccess($boxStore->getList(), $boxStore->getCount());
    }

    /**
     * @param Box[] $boxes
     *
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function save(
        ModelManager $modelManager,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModels(Box::class)] array $boxes
    ): AjaxResponse {
        $newTags = [];

        foreach ($boxes as $box) {
            foreach ($box->getItems() as $item) {
                foreach ($item->getTags() as $tag) {
                    $tagTag = $tag->getTag();

                    if ($tagTag->getId() !== null && $tagTag->getId() !== 0) {
                        continue;
                    }

                    if (isset($newTags[$tagTag->getName()])) {
                        $tag->setTag($tagTag);

                        continue;
                    }

                    $modelManager->save($tagTag);
                    $newTags[$tagTag->getName()] = $tagTag;
                }
            }

            $modelManager->save($box);
        }

        return $this->returnSuccess($boxes);
    }
}
