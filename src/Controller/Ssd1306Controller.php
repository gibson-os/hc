<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\Ssd1306\PixelMapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Slave\Ssd1306Service;
use GibsonOS\Module\Hc\Store\Ssd1306\PixelStore;
use JsonException;
use ReflectionException;

class Ssd1306Controller extends AbstractController
{
    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        PixelStore $pixelStore,
        #[GetModel(['id' => 'moduleId'])] Module $module,
    ): AjaxResponse {
        $pixelStore->setModule($module);

        return $this->returnSuccess($pixelStore->getList(), $pixelStore->getCount());
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function change(
        Ssd1306Service $ssd1306Service,
        PixelMapper $pixelMapper,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        array $data
    ): AjaxResponse {
        $ssd1306Service->writePixels(
            $module,
            $pixelMapper->completePixels($pixelMapper->mapFromDataArray($data))
        );

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function displayOn(
        Ssd1306Service $ssd1306Service,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        bool $on
    ): AjaxResponse {
        if ($on) {
            $ssd1306Service->setDisplayOn($module);
        } else {
            $ssd1306Service->setDisplayOff($module);
        }

        return $this->returnSuccess();
    }
}
