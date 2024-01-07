<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Neopixel\AnimationService;
use GibsonOS\Module\Hc\Store\Neopixel\AnimationStore;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class NeopixelAnimationController extends AbstractController
{
    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     */
    #[CheckPermission([Permission::READ])]
    public function get(
        AnimationRepository $animationRepository,
        ModelWrapper $modelWrapper,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        try {
            $startedAnimation = $animationRepository->getStarted($module);
        } catch (SelectError) {
            $startedAnimation = new Animation($modelWrapper);
        }

        try {
            $transmittedAnimation = $animationRepository->getTransmitted($module);
        } catch (SelectError) {
            $transmittedAnimation = new Animation($modelWrapper);
        }

        $return = $startedAnimation->jsonSerialize();
        $return['leds'] = $startedAnimation->getLeds();
        $return['transmitted'] = $transmittedAnimation;

        return $this->returnSuccess($return);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws ClientException
     * @throws RecordException
     */
    #[CheckPermission([Permission::READ])]
    public function getList(
        AnimationStore $animationStore,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        $animationStore->setModule($module);

        return $this->returnSuccess(
            $animationStore->getList(),
            $animationStore->getCount(),
        );
    }

    #[CheckPermission([Permission::READ])]
    public function getLoad(#[GetModel] Animation $animation): AjaxResponse
    {
        return $this->returnSuccess($animation->getLeds());
    }

    /**
     * @throws ClientException
     * @throws ImageExists
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     */
    #[CheckPermission([Permission::WRITE], ['id' => [Permission::WRITE, Permission::DELETE]])]
    public function post(
        ModelManager $modelManager,
        ?int $id,
        #[GetMappedModel(['name' => 'name', 'module_id' => 'moduleId'])]
        Animation $animation,
    ): AjaxResponse {
        if ($animation->getId() !== null && $animation->getId() !== $id) {
            throw new ImageExists(
                (int) $animation->getId(),
                sprintf(
                    'Es existiert schon eine Animation unter dem Namen "%s"' . PHP_EOL . 'Möchten Sie es überschreiben?',
                    $animation->getName() ?? 'NULL',
                ),
            );
        }

        $modelManager->save($animation);

        return $this->returnSuccess($animation);
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
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
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
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
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
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
        int $iterations = 0,
    ): AjaxResponse {
        $animationService->start($module, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postPause(
        AnimationService $animationService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        $animationService->pause($module);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws ClientException
     * @throws FactoryError
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function postStop(
        AnimationService $animationService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
    ): AjaxResponse {
        return $animationService->stop($module)
            ? $this->returnSuccess()
            : $this->returnFailure('No animation stopped.')
        ;
    }
}
