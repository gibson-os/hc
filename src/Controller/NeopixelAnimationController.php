<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use Exception;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Attribute\PermissionAttribute;
use GibsonOS\Core\Service\PermissionService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\AnimationService as AnimationAttributeService;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\LedService;
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
    public function index(AnimationAttributeService $animationService, ModuleRepository $moduleRepository, int $moduleId): AjaxResponse
    {
        $slave = $moduleRepository->getById($moduleId);

        return new AjaxResponse([
            'pid' => $animationService->getPid($slave),
            'started' => $animationService->getStarted($slave),
            'steps' => $animationService->getSteps($slave),
            'transmitted' => $animationService->isTransmitted($slave),
            'success' => true,
            'failure' => false,
        ]);
    }

    /**
     * @throws GetError
     */
    #[CheckPermission(Permission::READ)]
    public function list(AnimationStore $animationStore, int $moduleId): AjaxResponse
    {
        $animationStore->setSlave($moduleId);

        return $this->returnSuccess(
            $animationStore->getList(),
            $animationStore->getCount()
        );
    }

    /**
     * @throws DateTimeError
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
     * @throws DateTimeError
     * @throws DeleteError
     * @throws GetError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE, ['id' => Permission::WRITE + Permission::DELETE])]
    public function save(
        AnimationSequenceService $animationService,
        AnimationStore $animationStore,
        ModuleRepository $moduleRepository,
        int $moduleId,
        string $name,
        array $items,
        int $id = null
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);

        if (empty($id)) {
            try {
                $animation = $animationService->getByName($slave, $name);

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
            $slave,
            $name,
            $animationService->transformToTimeSteps($items),
            $id
        );
        $animationStore->setSlave($moduleId);

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
        ModuleRepository $moduleRepository,
        int $moduleId,
        array $items = []
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $steps = $animationSequenceService->transformToTimeSteps($items);
        $runtimes = $animationSequenceService->getRuntimes($steps);
        $msPerStep = 1000 / $slave->getPwmSpeed();
        $newLeds = [];

        $neopixelService->writeSequenceNew($slave);

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

                $neopixelService->writeSequenceAddStep($slave, $runtime, $changedLeds);
                $changedLeds = [];
            } while ($runtime === 65535);
        }

        $animationAttributeService->setSteps($slave, $steps, true);

        return $this->returnSuccess();
    }

    /**
     * @throws DateTimeError
     * @throws DeleteError
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function play(
        AnimationSequenceService $animationSequenceService,
        AnimationAttributeService $animationAttributeService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $iterations,
        array $items = []
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $steps = $animationSequenceService->transformToTimeSteps($items);
        $animationAttributeService->setSteps($slave, $steps, false);
        $animationSequenceService->play($slave, $iterations);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function start(
        //AnimationAttributeService $animationService,
        NeopixelService $neopixelService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $iterations = 0
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeSequenceStart($slave, $iterations);
        // @todo refactor. Sollte mit dem locker arbeiten um laufende prozesse zu setzen
        //$animationService->setStarted($slave, true);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function pause(
        //AnimationAttributeService $animationService,
        NeopixelService $neopixelService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeSequencePause($slave);
        //$animationService->setStarted($slave, false);

        return $this->returnSuccess();
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function stop(
        //AnimationAttributeService $animationService,
        NeopixelService $neopixelService,
        ModuleRepository $moduleRepository,
        int $moduleId
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $neopixelService->writeSequenceStop($slave);
        //$animationService->setStarted($slave, false);

        return $this->returnSuccess();
    }
}
