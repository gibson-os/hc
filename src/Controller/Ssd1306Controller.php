<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\Ssd1306\PixelMapper;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\Ssd1306Service;
use GibsonOS\Module\Hc\Store\Ssd1306\PixelStore;

class Ssd1306Controller extends AbstractController
{
    /**
     * @throws GetError
     */
    #[CheckPermission(Permission::READ)]
    public function index(PixelStore $pixelStore): AjaxResponse
    {
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
        ModuleRepository $moduleRepository,
        Ssd1306Service $ssd1306Service,
        PixelMapper $pixelMapper,
        int $moduleId,
        array $data
    ): AjaxResponse {
        $ssd1306Service->writePixels(
            $moduleRepository->getById($moduleId),
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
        ModuleRepository $moduleRepository,
        Ssd1306Service $ssd1306Service,
        int $moduleId,
        bool $on
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        if ($on) {
            $ssd1306Service->setDisplayOn($slave);
        } else {
            $ssd1306Service->setDisplayOff($slave);
        }

        return $this->returnSuccess();
    }
}