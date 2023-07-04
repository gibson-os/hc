<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;

class RhinetowerController extends AbstractController
{
    #[CheckPermission([Permission::WRITE])]
    public function postClock(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
    ): AjaxResponse {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::WRITE])]
    public function getClock(): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::WRITE])]
    public function postAnimation(int $id): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::WRITE])]
    public function post(array $leds, bool $withClock = false): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::READ], ['renew' => [Permission::WRITE]])]
    public function get(): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::READ])]
    public function getImage(int $id): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::WRITE])]
    public function postImage(int $id): AjaxResponse
    {
        return $this->returnSuccess();
    }

    #[CheckPermission([Permission::DELETE])]
    public function deleteImage(int $id): AjaxResponse
    {
        return $this->returnSuccess();
    }
}
