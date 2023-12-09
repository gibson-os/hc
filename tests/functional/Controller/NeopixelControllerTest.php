<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\NeopixelController;
use GibsonOS\Module\Hc\Exception\Neopixel\ImageExists;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Neopixel\ImageRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\LedRepository;
use GibsonOS\Module\Hc\Service\Module\NeopixelService;
use GibsonOS\Module\Hc\Service\Neopixel\LedService;
use GibsonOS\Module\Hc\Store\Neopixel\ImageStore;
use GibsonOS\Module\Hc\Store\Neopixel\LedStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class NeopixelControllerTest extends HcFunctionalTest
{
    private NeopixelController $neopixelController;

    protected function _before(): void
    {
        parent::_before();

        $this->neopixelController = $this->serviceManager->get(NeopixelController::class);
    }

    public function testGet(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $led1 = (new Led($this->modelWrapper))
            ->setNumber(3)
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($led1);
        $led2 = (new Led($this->modelWrapper))
            ->setNumber(2)
            ->setModule($module)
            ->setTop(10)
        ;
        $modelManager->saveWithoutChildren($led2);
        $led3 = (new Led($this->modelWrapper))
            ->setNumber(1)
            ->setModule($module)
            ->setLeft(10)
        ;
        $modelManager->saveWithoutChildren($led3);

        $this->checkSuccessResponse(
            $this->neopixelController->get(
                $this->serviceManager->get(LedStore::class),
                $module,
            ),
            [
                $led3->jsonSerialize(),
                $led2->jsonSerialize(),
                $led1->jsonSerialize(),
            ],
            3
        );
    }

    public function testPostShowLeds(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        )
            ->setConfig(json_encode([
                'counts' => [3],
                'channels' => 1,
            ]))
        ;

        $this->prophesizeWrite($module, 2, chr(0) . chr(3));

        $this->checkSuccessResponse(
            $this->neopixelController->postShowLeds(
                $this->serviceManager->get(NeopixelService::class),
                $module,
            )
        );
    }

    public function testPostLeds(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        )
            ->setConfig(json_encode([
                'counts' => [0],
                'channels' => 1,
            ]))
        ;

        $led1 = (new Led($this->modelWrapper))
            ->setNumber(0)
            ->setModule($module)
        ;
        $led2 = (new Led($this->modelWrapper))
            ->setNumber(2)
            ->setModule($module)
            ->setTop(10)
        ;
        $led3 = (new Led($this->modelWrapper))
            ->setNumber(1)
            ->setModule($module)
            ->setLeft(10)
        ;

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->saveWithoutChildren(
            (new Led($this->modelWrapper))
                ->setNumber(3)
                ->setModule($module)
        );

        $this->prophesizeWrite($module, 1, chr(0) . chr(3));

        $this->checkSuccessResponse(
            $this->neopixelController->postLeds(
                $this->serviceManager->get(NeopixelService::class),
                $this->serviceManager->get(LedService::class),
                $this->serviceManager->get(ModelManager::class),
                $this->serviceManager->get(LedRepository::class),
                $module,
                [$led1, $led2, $led3],
            )
        );

        /** @var LedRepository $ledRepository */
        $ledRepository = $this->serviceManager->get(LedRepository::class);
        $leds = $ledRepository->getByModule($module);

        $this->assertCount(3, $leds);
        $this->assertEquals($led1->jsonSerialize(), $leds[0]->jsonSerialize());
        $this->assertEquals($led3->jsonSerialize(), $leds[1]->jsonSerialize());
        $this->assertEquals($led2->jsonSerialize(), $leds[2]->jsonSerialize());
    }

    public function testPost(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        )
            ->setConfig(json_encode([
                'counts' => [3],
                'channels' => 1,
            ]))
        ;

        $this->prophesizeWrite($module, 2, chr(0) . chr(3));

        $this->checkSuccessResponse(
            $this->neopixelController->post(
                $this->serviceManager->get(NeopixelService::class),
                $this->serviceManager->get(LedService::class),
                $module,
                [2]
            )
        );
    }

    public function testGetImages(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $led = (new Led($this->modelWrapper))
            ->setNumber(0)
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($led);
        $image1 = (new Image($this->modelWrapper))
            ->setName('Marvin')
            ->setModule($module)
            ->setLeds([(new Image\Led($this->modelWrapper))->setLed($led)])
        ;
        $modelManager->save($image1);
        $image2 = (new Image($this->modelWrapper))
            ->setName('Galaxy')
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($image2);

        $this->checkSuccessResponse(
            $this->neopixelController->getImages(
                $this->serviceManager->get(ImageStore::class),
                $module,
            ),
            json_decode(json_encode([$image2, $image1]), true),
            2,
        );
    }

    public function testPostImage(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $image = (new Image($this->modelWrapper))
            ->setName('Marvin')
            ->setModule($module)
        ;

        $this->checkSuccessResponse(
            $this->neopixelController->postImage($modelManager, null, $image),
            $image->jsonSerialize(),
        );

        /** @var ImageRepository $imageRepository */
        $imageRepository = $this->serviceManager->get(ImageRepository::class);

        $this->assertEquals(
            $image->jsonSerialize(),
            $imageRepository->getByName($module, 'Marvin')->jsonSerialize(),
        );
    }

    public function testPostImageExists(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $image = (new Image($this->modelWrapper))
            ->setName('Marvin')
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($image);

        $this->expectException(ImageExists::class);

        $this->neopixelController->postImage($modelManager, null, $image);
    }

    public function testPostImageExistsOverwrite(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(6)
                ->setName('Neopixel')
                ->setHelper('neopixel'),
        );

        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $image = (new Image($this->modelWrapper))
            ->setName('Marvin')
            ->setModule($module)
        ;
        $modelManager->saveWithoutChildren($image);

        $this->checkSuccessResponse(
            $this->neopixelController->postImage($modelManager, $image->getId(), $image),
            $image->jsonSerialize(),
        );

        /** @var ImageRepository $imageRepository */
        $imageRepository = $this->serviceManager->get(ImageRepository::class);

        $this->assertEquals(
            $image->jsonSerialize(),
            $imageRepository->getByName($module, 'Marvin')->jsonSerialize(),
        );
    }
}
