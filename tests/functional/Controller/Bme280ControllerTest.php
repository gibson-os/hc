<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\Bme280Controller;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Mapper\Bme280Mapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\LogRepository;
use GibsonOS\Module\Hc\Service\Module\Bme280Service;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class Bme280ControllerTest extends HcFunctionalTest
{
    private Bme280Controller $bme280Controller;

    protected function _before(): void
    {
        parent::_before();

        $this->bme280Controller = $this->serviceManager->get(Bme280Controller::class);
    }

    public function testGetMeasure(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(256)
                ->setName('BME 280')
                ->setHelper('bme280'),
        );

        $this->prophesizeWrite($module, 242, chr(2));
        $this->prophesizeWrite($module, 244, chr(73));
        $this->prophesizeRead($module, 247, 8);

        $this->checkSuccessResponse(
            $this->bme280Controller->getMeasure($this->serviceManager->get(Bme280Service::class), $module),
            [
                'temperature' => 0,
                'pressure' => 178019175.2890816,
                'humidity' => 0,
            ]
        );
    }

    public function testGetNoLog(): void
    {
        $module = (new Module($this->modelWrapper));

        $this->expectException(SelectError::class);

        $this->bme280Controller->get(
            $this->serviceManager->get(Bme280Mapper::class),
            $this->serviceManager->get(LogRepository::class),
            $module,
        );
    }

    public function testGet(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(256)
                ->setName('BME 280')
                ->setHelper('bme280'),
        );
        $log = (new Log($this->modelWrapper))
            ->setMaster($module->getMaster())
            ->setModule($module)
            ->setType(255)
            ->setCommand(247)
            ->setDirection(Direction::INPUT)
        ;
        $modelManager->saveWithoutChildren($log);

        $this->checkSuccessResponse(
            $this->bme280Controller->get(
                $this->serviceManager->get(Bme280Mapper::class),
                $this->serviceManager->get(LogRepository::class),
                $module,
            ),
            [
                'temperature' => 0,
                'pressure' => 178019175.2890816,
                'humidity' => 0,
            ],
        );
    }
}
