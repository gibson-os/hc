<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Controller\NeopixelAnimationController;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Service\Neopixel\AnimationService;
use GibsonOS\Module\Hc\Store\Neopixel\AnimationStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class NeopixelAnimationControllerTest extends HcFunctionalTest
{
    private NeopixelAnimationController $neopixelAnimationController;

    protected function _before(): void
    {
        parent::_before();

        $this->neopixelAnimationController = $this->serviceManager->get(NeopixelAnimationController::class);
    }

    public function testGetNoAnimation(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setStarted(false)
            ->setTransmitted(false)
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($animation);

        $response = $this->neopixelAnimationController->get(
            $this->serviceManager->get(AnimationRepository::class),
            $this->serviceManager->get(ModelWrapper::class),
            $module,
        );

        $this->checkSuccessResponse(
            $response,
            [
                'id' => 0,
                'name' => null,
                'pid' => null,
                'started' => false,
                'leds' => [],
                'transmitted' => [
                    'id' => 0,
                    'name' => null,
                    'pid' => null,
                    'started' => false,
                    'transmitted' => false,
                    'paused' => false,
                ],
                'paused' => false,
            ],
        );
    }

    public function testGetStarted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setStarted(true)
            ->setTransmitted(false)
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($animation);

        $response = $this->neopixelAnimationController->get(
            $this->serviceManager->get(AnimationRepository::class),
            $this->serviceManager->get(ModelWrapper::class),
            $module,
        );

        $this->checkSuccessResponse(
            $response,
            [
                'id' => 1,
                'name' => 'galaxy',
                'pid' => null,
                'started' => true,
                'leds' => [],
                'transmitted' => [
                    'id' => 0,
                    'name' => null,
                    'pid' => null,
                    'started' => false,
                    'transmitted' => false,
                    'paused' => false,
                ],
                'paused' => false,
            ],
        );
    }

    public function testGetTransmitted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setStarted(false)
            ->setTransmitted(true)
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($animation);

        $response = $this->neopixelAnimationController->get(
            $this->serviceManager->get(AnimationRepository::class),
            $this->serviceManager->get(ModelWrapper::class),
            $module,
        );

        $this->checkSuccessResponse(
            $response,
            [
                'id' => 0,
                'name' => null,
                'pid' => null,
                'started' => false,
                'leds' => [],
                'transmitted' => [
                    'id' => 1,
                    'name' => 'galaxy',
                    'pid' => null,
                    'started' => false,
                    'transmitted' => true,
                    'paused' => false,
                ],
                'paused' => false,
            ],
        );
    }

    public function testGetStartedTransmitted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setStarted(true)
            ->setTransmitted(true)
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($animation);

        $response = $this->neopixelAnimationController->get(
            $this->serviceManager->get(AnimationRepository::class),
            $this->serviceManager->get(ModelWrapper::class),
            $module,
        );

        $this->checkSuccessResponse(
            $response,
            [
                'id' => 1,
                'name' => 'galaxy',
                'pid' => null,
                'started' => true,
                'leds' => [],
                'transmitted' => [
                    'id' => 1,
                    'name' => 'galaxy',
                    'pid' => null,
                    'started' => true,
                    'transmitted' => true,
                    'paused' => false,
                ],
                'paused' => false,
            ],
        );
    }

    public function testGetList(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation1 = (new Animation($this->modelWrapper))
            ->setName('marvin')
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($animation1);
        $animation2 = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($animation2);

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->getList(
                $this->serviceManager->get(AnimationStore::class),
                $module,
            ),
            [
                $animation2->jsonSerialize(),
                $animation1->jsonSerialize(),
            ],
            2,
        );
    }

    public function testPostPlay(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
        ;

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->postPlay(
                $this->serviceManager->get(AnimationService::class),
                $animation,
                0,
            )
        );

        $animationRepository = $this->serviceManager->get(AnimationRepository::class);

        $this->assertEquals(
            $animation->jsonSerialize(),
            $animationRepository->getByName($module, 'galaxy')->jsonSerialize(),
        );
    }

    public function testPostStart(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(true)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->postStart(
                $this->serviceManager->get(AnimationService::class),
                $module,
            )
        );

        $animationRepository = $this->serviceManager->get(AnimationRepository::class);
        $animation->setStarted(true);

        $this->assertEquals(
            $animation->jsonSerialize(),
            $animationRepository->getByName($module, 'galaxy')->jsonSerialize(),
        );
    }

    public function testPostStartNotTransmitted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(false)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->expectException(SelectError::class);

        $this->neopixelAnimationController->postStart(
            $this->serviceManager->get(AnimationService::class),
            $module,
        );
    }

    public function testPostPause(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(false)
            ->setStarted(true)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->postPause(
                $this->serviceManager->get(AnimationService::class),
                $module,
            )
        );

        $animationRepository = $this->serviceManager->get(AnimationRepository::class);
        $animation
            ->setPaused(true)
            ->setStarted(false)
        ;

        $this->assertEquals(
            $animation->jsonSerialize(),
            $animationRepository->getByName($module, 'galaxy')->jsonSerialize(),
        );
    }

    public function testPostPauseTransmitted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(true)
            ->setStarted(true)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->prophesizeWrite($module, 11, '');

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->postPause(
                $this->serviceManager->get(AnimationService::class),
                $module,
            )
        );

        $animationRepository = $this->serviceManager->get(AnimationRepository::class);
        $animation
            ->setPaused(true)
            ->setStarted(false)
        ;

        $this->assertEquals(
            $animation->jsonSerialize(),
            $animationRepository->getByName($module, 'galaxy')->jsonSerialize(),
        );
    }

    public function testPostPauseNotStarted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(true)
            ->setStarted(false)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->expectException(SelectError::class);

        $this->neopixelAnimationController->postPause(
            $this->serviceManager->get(AnimationService::class),
            $module,
        );
    }

    public function testPostStop(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(false)
            ->setStarted(true)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->postStop(
                $this->serviceManager->get(AnimationService::class),
                $module,
            )
        );

        $animationRepository = $this->serviceManager->get(AnimationRepository::class);
        $animation
            ->setPaused(false)
            ->setStarted(false)
        ;

        $this->assertEquals(
            $animation->jsonSerialize(),
            $animationRepository->getByName($module, 'galaxy')->jsonSerialize(),
        );
    }

    public function testPostStopTransmitted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(true)
            ->setStarted(true)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->prophesizeWrite($module, 12, '');

        $this->checkSuccessResponse(
            $this->neopixelAnimationController->postStop(
                $this->serviceManager->get(AnimationService::class),
                $module,
            )
        );

        $animationRepository = $this->serviceManager->get(AnimationRepository::class);
        $animation
            ->setPaused(false)
            ->setStarted(false)
        ;

        $this->assertEquals(
            $animation->jsonSerialize(),
            $animationRepository->getByName($module, 'galaxy')->jsonSerialize(),
        );
    }

    public function testPostStopNotStarted(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $animation = (new Animation($this->modelWrapper))
            ->setName('galaxy')
            ->setModule($module)
            ->setTransmitted(true)
            ->setStarted(false)
        ;
        $modelManager->saveWithoutChildren($animation);

        $this->checkErrorResponse(
            $this->neopixelAnimationController->postStop(
                $this->serviceManager->get(AnimationService::class),
                $module,
            ),
            'No animation stopped.',
        );
    }
}
