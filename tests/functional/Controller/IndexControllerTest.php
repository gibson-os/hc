<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use DateTimeImmutable;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Controller\IndexController;
use GibsonOS\Module\Hc\Dto\Direction;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Store\LogStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class IndexControllerTest extends HcFunctionalTest
{
    private IndexController $indexController;

    protected function _before(): void
    {
        parent::_before();

        $this->indexController = $this->serviceManager->get(IndexController::class);
    }

    /**
     * @dataProvider getLogData
     */
    public function testGetLog(
        array $expected,
        int $masterId = null,
        int $moduleId = null,
        array $directions = [],
        array $types = [],
        array $sort = [],
    ): void {
        $date = new DateTimeImmutable('2023-11-03 00:00:00');
        /** @var ModelManager $modelManager */
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $masterArthur = (new Master($this->modelWrapper))
            ->setName('arthur')
            ->setProtocol('udp')
            ->setAddress('42.42.42.1')
            ->setSendPort(42)
        ;
        $masterDent = (new Master($this->modelWrapper))
            ->setName('dent')
            ->setProtocol('udp')
            ->setAddress('42.42.42.2')
            ->setSendPort(42)
        ;
        $modelManager->saveWithoutChildren($masterArthur);
        $modelManager->saveWithoutChildren($masterDent);
        $modelManager->saveWithoutChildren(
            (new Log($this->modelWrapper))
                ->setMaster($masterArthur)
                ->setType(42)
                ->setDirection(Direction::INPUT)
                ->setAdded($date)
        );
        $modelManager->saveWithoutChildren(
            (new Log($this->modelWrapper))
                ->setMaster($masterArthur)
                ->setType(24)
                ->setDirection(Direction::OUTPUT)
                ->setAdded($date)
        );
        $modelManager->saveWithoutChildren(
            (new Log($this->modelWrapper))
                ->setMaster($masterDent)
                ->setType(42)
                ->setDirection(Direction::OUTPUT)
                ->setAdded($date)
        );
        $type = (new Type($this->modelWrapper))
            ->setId(42)
            ->setName('prefect')
            ->setHelper('marvin')
        ;
        $modelManager->saveWithoutChildren($type);
        $module = (new Module($this->modelWrapper))
            ->setName('ford')
            ->setAddress(42)
            ->setType($type)
        ;
        $modelManager->saveWithoutChildren($module);
        $modelManager->saveWithoutChildren(
            (new Log($this->modelWrapper))
                ->setMaster($masterDent)
                ->setModule($module)
                ->setSlaveAddress($module->getAddress())
                ->setType(42)
                ->setDirection(Direction::OUTPUT)
                ->setAdded($date)
        );

        $response = $this->indexController->getLog(
            $this->serviceManager->get(LogStore::class),
            $masterId,
            $moduleId,
            $directions,
            $types,
            $sort,
        );

        $this->checkSuccessResponse($response, $expected, count($expected));
    }

    public function getLogData(): array
    {
        return [
            'all' => [
                [
                    [
                        'id' => 4,
                        'moduleId' => 1,
                        'moduleName' => 'ford',
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => 42,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => 'marvin',
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 3,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 2,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 24,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 1,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'input',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
            ],
            'direction input' => [
                [
                    [
                        'id' => 1,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'input',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
                null,
                null,
                [Direction::INPUT->name],
            ],
            'direction output' => [
                [
                    [
                        'id' => 4,
                        'moduleId' => 1,
                        'moduleName' => 'ford',
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => 42,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => 'marvin',
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 3,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 2,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 24,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
                null,
                null,
                [Direction::OUTPUT->name],
            ],
            'master arthur' => [
                [
                    [
                        'id' => 2,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 24,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 1,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'input',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
                1,
            ],
            'master dent' => [
                [
                    [
                        'id' => 4,
                        'moduleId' => 1,
                        'moduleName' => 'ford',
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => 42,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => 'marvin',
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 3,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
                2,
            ],
            'module' => [
                [
                    [
                        'id' => 4,
                        'moduleId' => 1,
                        'moduleName' => 'ford',
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => 42,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => 'marvin',
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
                null,
                1,
            ],
            'type' => [
                [
                    [
                        'id' => 4,
                        'moduleId' => 1,
                        'moduleName' => 'ford',
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => 42,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => 'marvin',
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 3,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 2,
                        'masterName' => 'dent',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'output',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                    [
                        'id' => 1,
                        'moduleId' => null,
                        'moduleName' => null,
                        'masterId' => 1,
                        'masterName' => 'arthur',
                        'added' => '2023-11-03 00:00:00',
                        'slaveAddress' => null,
                        'type' => 42,
                        'command' => null,
                        'data' => '',
                        'direction' => 'input',
                        'helper' => null,
                        'text' => null,
                        'rendered' => null,
                        'explains' => null,
                    ],
                ],
                null,
                null,
                [],
                [42],
            ],
        ];
    }
}
