<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;

class RhinetowerController extends AbstractController
{
    #[CheckPermission(Permission::WRITE)]
    public function setClock(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
    ): AjaxResponse {
        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::WRITE)]
    public function showClock(): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::WRITE)]
    public function playAnimation(int $id): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::WRITE)]
    public function set(array $leds, bool $withClock = false): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ, ['renew' => Permission::WRITE])]
    public function status(): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ)]
     public function image(int $id): AjaxResponse
     {
         return $this->returnSuccess();
     }

    #[CheckPermission(Permission::WRITE)]
     public function saveImage(int $id): AjaxResponse
     {
         return $this->returnSuccess();
     }

    #[CheckPermission(Permission::DELETE)]
     public function deleteImage(int $id): AjaxResponse
     {
         return $this->returnSuccess();
     }
}
