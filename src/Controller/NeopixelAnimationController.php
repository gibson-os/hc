<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModel;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Neopixel\AnimationService;
use GibsonOS\Module\Hc\Service\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Store\Neopixel\AnimationStore;
use JsonException;
use ReflectionException;

class NeopixelAnimationController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws Exception
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        AnimationRepository $animationRepository,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        try {
            $animation = $animationRepository->getActive($module);
        } catch (SelectError $e) {
            $animation = new Animation();
        }

        $return = $animation->jsonSerialize();
        $return['success'] = true;
        $return['failure'] = false;

        return new AjaxResponse($return);
    }

    /**
     * @throws JsonException
     * @throws SelectError
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function list(
        AnimationStore $animationStore,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $animationStore->setModule($module);

        return $this->returnSuccess(
            $animationStore->getList(),
            $animationStore->getCount()
        );
    }

    #[CheckPermission(Permission::READ)]
    public function load(#[GetModel] Animation $animation): AjaxResponse
    {
        return $this->returnSuccess($animation->getLeds());
    }

    /**
     * @throws SaveError
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::WRITE, ['id' => Permission::WRITE + Permission::DELETE])]
    public function save(
        AnimationStore $animationStore,
        ModelManager $modelManager,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModel(['name' => 'name'], ['module' => 'module'])] Animation $animation
    ): AjaxResponse {
        if ($animation->getId() !== null) {
            new ImageExists(
                (int) $animation->getId(),
                sprintf(
                    'Es existiert schon eine Animation unter dem Namen "%s"' . PHP_EOL . 'Möchten Sie es überschreiben?',
                    $animation->getName() ?? 'NULL'
                )
            );
        }

        $modelManager->save($animation);
        $animationStore->setModule($animation->getModule());

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
    #[CheckPermission(Permission::WRITE)]
    public function send(
        LedService $ledService,
        NeopixelService $neopixelService,
        AnimationService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModel(['id' => 'id'], ['module' => 'module'])] Animation $animation,
        array $items = []
    ): AjaxResponse {
        $steps = $animationSequenceService->transformToTimeSteps($items);
        $runtimes = $animationSequenceService->getRuntimes($steps);
        $msPerStep = 1000 / $module->getPwmSpeed();
        $newLeds = [];

        $neopixelService->writeSequenceNew($module);

        foreach ($steps as $time => $leds) {
            $oldLeds = $newLeds;
            $newLeds = [];

            foreach ($leds as $led) {
                $newLeds[$led->getLed()->getNumber()] = $led;
            }

            $changedLeds = $ledService->getChanges($oldLeds, $newLeds);
            $pwmSteps = (int) ceil($msPerStep * $runtimes[$time]);

            do {
                $runtime = $pwmSteps;

                if ($runtime > 65535) {
                    $pwmSteps -= 65535;
                    $runtime = 65535;
                }

                $neopixelService->writeSequenceAddStep($module, $runtime, $changedLeds);
                $changedLeds = [];
            } while ($runtime === 65535);
        }

        $animationAttributeService->setSteps($module, $steps, true);

        return $this->returnSuccess();
    }

    /**
     * @throws DeleteError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     * @throws FactoryError
     * @throws MapperException
     */
    #[CheckPermission(Permission::WRITE)]
    public function play(
        AnimationService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModel(mapping: ['module' => 'module'])] Animation $animation,
        int $iterations,
    ): AjaxResponse {
//        $steps = $animationSequenceService->transformToTimeSteps($items);
//        $animationAttributeService->setSteps($module, $steps, false);
//        $animationSequenceService->play($module, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function start(
        NeopixelService $neopixelService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $iterations = 0
    ): AjaxResponse {
        $neopixelService->writeSequenceStart($module, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function pause(
        NeopixelService $neopixelService,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $neopixelService->writeSequencePause($module);
        // $animationService->setStarted($slave, false);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function stop(
        AnimationService $animationService,
        NeopixelService $neopixelService,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $animationService->stop($module);
        $neopixelService->writeSequenceStop($module);

        return $this->returnSuccess();
    }
}
