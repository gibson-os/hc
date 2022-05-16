<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Neopixel\LedService;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationSequenceService;
use GibsonOS\Module\Hc\Service\Slave\NeopixelService;
use GibsonOS\Module\Hc\Store\Neopixel\AnimationStore;
use JsonException;

class NeopixelAnimationController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws Exception
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        AnimationAttributeService $animationService,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        return new AjaxResponse([
            'pid' => $animationService->getPid($module),
            'started' => $animationService->getStarted($module),
            'steps' => $animationService->getSteps($module),
            'transmitted' => $animationService->isTransmitted($module),
            'success' => true,
            'failure' => false,
        ]);
    }

    #[CheckPermission(Permission::READ)]
    public function list(AnimationStore $animationStore, int $moduleId): AjaxResponse
    {
        $animationStore->setModuleId($moduleId);

        return $this->returnSuccess(
            $animationStore->getList(),
            $animationStore->getCount()
        );
    }

    /**
     * @throws SelectError
     * @throws JsonException
     */
    #[CheckPermission(Permission::READ)]
    public function load(AnimationSequenceService $animationService, int $id): AjaxResponse
    {
        $steps = $animationService->getById($id);
        $items = [];

        foreach ($steps as $step) {
            foreach ($step as $item) {
                $items[] = $item;
            }
        }

        return $this->returnSuccess($items);
    }

    /**
     * @throws DeleteError
     * @throws SaveError
     * @throws SelectError
     * @throws JsonException
     * @throws JsonException
     */
    #[CheckPermission(Permission::WRITE, ['id' => Permission::WRITE + Permission::DELETE])]
    public function save(
        AnimationSequenceService $animationService,
        AnimationStore $animationStore,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        string $name,
        array $items,
        int $id = null
    ): AjaxResponse {
        if (empty($id)) {
            try {
                $animation = $animationService->getByName($module, $name);

                new ImageExists(
                    (int) $animation->getId(),
                    sprintf(
                        'Es existiert schon eine Animation unter dem Namen "%s"' . PHP_EOL . 'Möchten Sie es überschreiben?',
                        $name
                    )
                );
            } catch (SelectError) {
                // New Animation
            }
        }

        $animation = $animationService->save(
            $module,
            $name,
            $animationService->transformToTimeSteps($module, $items),
            $id
        );
        $animationStore->setModuleId($module->getId() ?? 0);

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
     * @throws DateTimeError
     * @throws DeleteError
     * @throws SaveError
     * @throws SelectError
     * @throws WriteException
     */
    #[CheckPermission(Permission::WRITE)]
    public function send(
        LedService $ledService,
        NeopixelService $neopixelService,
        AnimationSequenceService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        array $items = []
    ): AjaxResponse {
        $steps = $animationSequenceService->transformToTimeSteps($module, $items);
        $runtimes = $animationSequenceService->getRuntimes($steps);
        $msPerStep = 1000 / $module->getPwmSpeed();
        $newLeds = [];

        $neopixelService->writeSequenceNew($module);

        foreach ($steps as $time => $leds) {
            $oldLeds = $newLeds;
            $newLeds = [];

            foreach ($leds as $led) {
                $newLeds[$led->getNumber()] = $led;
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
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function play(
        AnimationSequenceService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $iterations,
        array $items = []
    ): AjaxResponse {
        $steps = $animationSequenceService->transformToTimeSteps($module, $items);
        $animationAttributeService->setSteps($module, $steps, false);
        $animationSequenceService->play($module, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
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
     */
    #[CheckPermission(Permission::WRITE)]
    public function stop(
        AnimationSequenceService $animationService,
        NeopixelService $neopixelService,
        #[GetModel(['id' => 'moduleId'])] Module $module
    ): AjaxResponse {
        $animationService->stop($module);
        $neopixelService->writeSequenceStop($module);

        return $this->returnSuccess();
    }
}
