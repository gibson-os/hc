<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Mapper\Ssd1306\PixelMapper;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\Ssd1306Service;
use GibsonOS\Module\Hc\Store\Ssd1306\PixelStore;

class Ssd1306Controller extends AbstractController
{
    /**
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws GetError
     */
    public function index(PixelStore $pixelStore): AjaxResponse
    {
        $this->checkPermission(PermissionService::READ);

        return $this->returnSuccess($pixelStore->getList(), $pixelStore->getCount());
    }

    /**
     * @throws AbstractException
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    public function change(
        ModuleRepository $moduleRepository,
        Ssd1306Service $ssd1306Service,
        PixelMapper $pixelMapper,
        int $moduleId,
        array $data
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $ssd1306Service->writePixels(
            $moduleRepository->getById($moduleId),
            $pixelMapper->completePixels($pixelMapper->mapFromDataArray($data))
        );

        return $this->returnSuccess();
    }

    /**
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws AbstractException
     * @throws DateTimeError
     * @throws SaveError
     * @throws SelectError
     */
    public function displayOn(
        ModuleRepository $moduleRepository,
        Ssd1306Service $ssd1306Service,
        int $moduleId,
        bool $on
    ): AjaxResponse {
        $this->checkPermission(PermissionService::WRITE);

        $slave = $moduleRepository->getById($moduleId);

        if ($on) {
            $ssd1306Service->setDisplayOn($slave);
        } else {
            $ssd1306Service->setDisplayOff($slave);
        }

        return $this->returnSuccess();
    }
}