<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Neopixel\AnimationService;
use GibsonOS\Module\Hc\Store\Neopixel\AnimationStore;
use JsonException;
use ReflectionException;

class NeopixelAnimationController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[CheckPermission([Permission::READ])]
    public function get(
        AnimationRepository $animationRepository,
        #[GetModel(['id' => 'moduleId'])]
        Module $module
    ): AjaxResponse {
        try {
            $startedAnimation = $animationRepository->getStarted($module);
        } catch (SelectError) {
            $startedAnimation = new Animation();
        }

        try {
            $transmittedAnimation = $animationRepository->getTransmitted($module);
        } catch (SelectError) {
            $transmittedAnimation = new Animation();
        }

        $return = $startedAnimation->jsonSerialize();
        $return['success'] = true;
        $return['failure'] = false;
        $return['leds'] = $startedAnimation->getLeds();
        $return['transmitted'] = $transmittedAnimation;

        return new AjaxResponse($return);
    }

    /**
     * @throws JsonException
     * @throws SelectError
     * @throws ReflectionException
     */
    #[CheckPermission([Permission::READ])]
    public function getList(
        AnimationStore $animationStore,
        #[GetModel(['id' => 'moduleId'])]
        Module $module
    ): AjaxResponse {
        $animationStore->setModule($module);

        return $this->returnSuccess(
            $animationStore->getList(),
            $animationStore->getCount()
        );
    }

    #[CheckPermission([Permission::READ])]
    public function getLoad(#[GetModel] Animation $animation): AjaxResponse
    {
        return $this->returnSuccess($animation->getLeds());
    }

    /**
     * @throws SaveError
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     * @throws ImageExists
     */
    #[CheckPermission([Permission::WRITE], ['id' => [Permission::WRITE, Permission::DELETE]])]
    public function post(
        AnimationStore $animationStore,
        ModelManager $modelManager,
        ?int $id,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        #[GetMappedModel(['name' => 'name', 'module_id' => 'moduleId'])]
        Animation $animation
    ): AjaxResponse {
        if ($animation->getId() !== null && $animation->getId() !== $id) {
            throw new ImageExists(
                (int) $animation->getId(),
                sprintf(
                    'Es existiert schon eine Animation unter dem Namen "%s"' . PHP_EOL . 'Möchten Sie es überschreiben?',
                    $animation->getName() ?? 'NULL'
                )
            );
        }

        $modelManager->save($animation);
        $animationStore->setModule($module);

        return new AjaxResponse([
            'data' => $animationStore->getList(),
            'total' => $animationStore->getCount(),
            'id' => $animation->getId(),
            'success' => true,
            'failure' => false,
        ]);
    }

    /**
     * @throws AbstractException
     * @throws DeleteError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     * @throws FactoryError
     * @throws MapperException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postSend(
        AnimationService $animationService,
        #[GetMappedModel(['id' => 'id', 'module_id' => 'moduleId'])]
        Animation $animation,
    ): AjaxResponse {
        $animationService->send($animation);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postPlay(
        AnimationService $animationService,
        #[GetMappedModel(['id' => 'id', 'module_id' => 'moduleId'])]
        Animation $animation,
        int $iterations,
    ): AjaxResponse {
        $animation->setName($animation->getName() ?? '');
        $animationService->play($animation, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postStart(
        AnimationService $animationService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        int $iterations = 0
    ): AjaxResponse {
        $animationService->start($module, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postPause(
        AnimationService $animationService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module
    ): AjaxResponse {
        $animationService->pause($module);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postStop(
        AnimationService $animationService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module
    ): AjaxResponse {
        return $animationService->stop($module)
            ? $this->returnSuccess()
            : $this->returnFailure('No animation stopped.')
        ;
    }
}
