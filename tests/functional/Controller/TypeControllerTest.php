<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Module\Hc\Controller\TypeController;
use GibsonOS\Module\Hc\Install\Data\TypeData;
use GibsonOS\Module\Hc\Store\TypeStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class TypeControllerTest extends HcFunctionalTest
{
    private TypeController $typeController;

    protected function _before(): void
    {
        parent::_before();

        $this->typeController = $this->serviceManager->get(TypeController::class);
    }

    public function testGet(): void
    {
        $this->serviceManager->get(TypeData::class)->install('hc')->current();

        $this->checkSuccessResponse(
            $this->typeController->get(
                $this->serviceManager->get(TypeStore::class),
            ),
            [
                [
                    'id' => 0,
                    'name' => 'Neues Modul',
                    'helper' => 'blank',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => '{"icon":"icon_bug"}',
                    'hasInput' => false,
                    'isHcSlave' => true,
                ], [
                    'id' => 4,
                    'name' => 'Rhinetower',
                    'helper' => 'rhinetower',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => '{"icon":"icon_rhinetower"}',
                    'hasInput' => false,
                    'isHcSlave' => true,
                ], [
                    'id' => 6,
                    'name' => 'Neopixel',
                    'helper' => 'neopixel',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => '{"icon":"icon_led"}',
                    'hasInput' => false,
                    'isHcSlave' => true,
                ], [
                    'id' => 7,
                    'name' => 'IR',
                    'helper' => 'ir',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => '{"icon":"icon_remotecontrol"}',
                    'hasInput' => true,
                    'isHcSlave' => true,
                ], [
                    'id' => 8,
                    'name' => 'I/O',
                    'helper' => 'io',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => null,
                    'hasInput' => true,
                    'isHcSlave' => true,
                ], [
                    'id' => 9,
                    'name' => 'Warehouse',
                    'helper' => 'warehouse',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => null,
                    'hasInput' => false,
                    'isHcSlave' => true,
                ], [
                    'id' => 255,
                    'name' => 'Neues Modul',
                    'helper' => 'blank',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => '{"icon":"icon_bug"}',
                    'hasInput' => false,
                    'isHcSlave' => true,
                ], [
                    'id' => 256,
                    'name' => 'BME 280',
                    'helper' => 'bme280',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => null,
                    'hasInput' => false,
                    'isHcSlave' => false,
                ], [
                    'id' => 257,
                    'name' => 'SSD1306',
                    'helper' => 'ssd1306',
                    'hertz' => 0,
                    'network' => false,
                    'uiSettings' => null,
                    'hasInput' => false,
                    'isHcSlave' => false,
                ],
            ],
            9
        );
    }
}
