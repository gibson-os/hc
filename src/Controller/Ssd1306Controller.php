<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
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
}