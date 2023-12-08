<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\Event;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Mock\Service\TestEvent;
use GibsonOS\Mock\Service\TestEventService;
use GibsonOS\Module\Hc\Controller\IrController;
use GibsonOS\Module\Hc\Dto\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Remote;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Ir\KeyRepository;
use GibsonOS\Module\Hc\Service\Module\IrService;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;
use GibsonOS\Module\Hc\Store\Ir\RemoteStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class IrControllerTest extends HcFunctionalTest
{
    private IrController $irController;

    protected function _before(): void
    {
        parent::_before();

        $this->serviceManager->setService(
            EventService::class,
            $this->serviceManager->get(TestEventService::class),
        );
        $this->irController = $this->serviceManager->get(IrController::class);
    }

    /**
     * @dataProvider getKeysData
     */
    public function testGetKeys(array $sort, array $result): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->save(
            (new Key($this->modelWrapper))
                ->setNames([
                    (new Key\Name($this->modelWrapper))->setName('Arthur'),
                    (new Key\Name($this->modelWrapper))->setName('Ford'),
                ])
                ->setCommand(1)
                ->setAddress(2)
                ->setProtocol(Protocol::NEC)
        );
        $modelManager->save(
            (new Key($this->modelWrapper))
                ->setNames([
                    (new Key\Name($this->modelWrapper))->setName('Dent'),
                    (new Key\Name($this->modelWrapper))->setName('Prefect'),
                ])
                ->setCommand(2)
                ->setAddress(1)
                ->setProtocol(Protocol::SAMSUNG)
        );

        $this->checkSuccessResponse(
            $this->irController->getKeys(
                $this->serviceManager->get(KeyStore::class),
                sort: $sort,
            ),
            $result,
            4
        );
    }

    public function getKeysData(): array
    {
        $keyArthur = [
            'id' => 1,
            'name' => 'Arthur',
            'names' => [
                ['id' => 1, 'name' => 'Arthur'],
            ],
            'protocol' => 2,
            'command' => 1,
            'address' => 2,
            'protocolName' => 'NEC',
        ];
        $keyDent = [
            'id' => 2,
            'name' => 'Dent',
            'names' => [
                ['id' => 3, 'name' => 'Dent'],
            ],
            'protocol' => 3,
            'command' => 2,
            'address' => 1,
            'protocolName' => 'Samsung',
        ];
        $keyFord = [
            'id' => 1,
            'name' => 'Ford',
            'names' => [
                ['id' => 2, 'name' => 'Ford'],
            ],
            'protocol' => 2,
            'command' => 1,
            'address' => 2,
            'protocolName' => 'NEC',
        ];
        $keyPrefect = [
            'id' => 2,
            'name' => 'Prefect',
            'names' => [
                ['id' => 4, 'name' => 'Prefect'],
            ],
            'protocol' => 3,
            'command' => 2,
            'address' => 1,
            'protocolName' => 'Samsung',
        ];

        return [
            'no sort' => [
                [],
                [$keyArthur, $keyDent, $keyFord, $keyPrefect],
            ],
            'name asc' => [
                [['property' => 'name', 'direction' => 'ASC']],
                [$keyArthur, $keyDent, $keyFord, $keyPrefect],
            ],
            'name desc' => [
                [['property' => 'name', 'direction' => 'DESC']],
                [$keyPrefect, $keyFord, $keyDent, $keyArthur],
            ],
            'protocol name asc' => [
                [['property' => 'protocolName', 'direction' => 'ASC']],
                [$keyArthur, $keyFord, $keyDent, $keyPrefect],
            ],
            'protocol name desc' => [
                [['property' => 'protocolName', 'direction' => 'DESC']],
                [$keyDent, $keyPrefect, $keyArthur, $keyFord],
            ],
            'address asc' => [
                [['property' => 'address', 'direction' => 'ASC']],
                [$keyDent, $keyPrefect, $keyArthur, $keyFord],
            ],
            'address desc' => [
                [['property' => 'address', 'direction' => 'DESC']],
                [$keyArthur, $keyFord, $keyDent, $keyPrefect],
            ],
            'command asc' => [
                [['property' => 'command', 'direction' => 'ASC']],
                [$keyArthur, $keyFord, $keyDent, $keyPrefect],
            ],
            'command desc' => [
                [['property' => 'command', 'direction' => 'DESC']],
                [$keyDent, $keyPrefect, $keyArthur, $keyFord],
            ],
        ];
    }

    public function testPostKey(): void
    {
        $key = (new Key($this->modelWrapper))
            ->setProtocol(Protocol::NEC)
            ->setAddress(42)
            ->setCommand(24)
        ;

        $this->checkSuccessResponse(
            $this->irController->postKey(
                $this->serviceManager->get(ModelManager::class),
                $this->serviceManager->get(ModelWrapper::class),
                $key,
                'galaxy',
            )
        );

        $keyById = $this->serviceManager->get(KeyRepository::class)->getById($key->getId());

        $this->assertEquals($key->jsonSerialize(), $keyById->jsonSerialize());
        $this->assertCount(1, $keyById->getNames());
        $this->assertEquals('galaxy', $keyById->getNames()[0]->getName());
    }

    public function testDeleteKeys(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $key1 = (new Key($this->modelWrapper))
            ->setProtocol(Protocol::NEC)
            ->setAddress(42)
            ->setCommand(24)
        ;
        $modelManager->saveWithoutChildren($key1);
        $key2 = (new Key($this->modelWrapper))
            ->setProtocol(Protocol::SAMSUNG)
            ->setAddress(24)
            ->setCommand(42)
        ;
        $modelManager->saveWithoutChildren($key2);

        $this->checkSuccessResponse(
            $this->irController->deleteKeys(
                $modelManager,
                [$key1]
            )
        );

        $keyById = $this->serviceManager->get(KeyRepository::class)->getById($key2->getId());
        $this->assertEquals($key2->jsonSerialize(), $keyById->jsonSerialize());

        $this->expectException(SelectError::class);
        $this->serviceManager->get(KeyRepository::class)->getById($key1->getId());
    }

    public function testPost(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(7)
                ->setName('IR')
                ->setHelper('ir'),
        );
        $key = (new Key($this->modelWrapper))
            ->setProtocol(Protocol::NEC)
            ->setAddress(42)
            ->setCommand(24)
        ;
        $this->prophesizeWrite(
            $module,
            0,
            chr(2) . chr(0) . chr(42) . chr(0) . chr(24),
        );

        $this->checkSuccessResponse(
            $this->irController->post(
                $this->serviceManager->get(IrService::class),
                $module,
                $key,
            )
        );
    }

    public function testGetRemotes(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $remoteArthur = (new Remote($this->modelWrapper))
            ->setName('arthur')
        ;
        $modelManager->saveWithoutChildren($remoteArthur);
        $remoteDent = (new Remote($this->modelWrapper))
            ->setName('dent')
        ;
        $modelManager->saveWithoutChildren($remoteDent);
        $remoteFord = (new Remote($this->modelWrapper))
            ->setName('ford')
        ;
        $modelManager->saveWithoutChildren($remoteFord);
        $remotePrefect = (new Remote($this->modelWrapper))
            ->setName('prefect')
        ;
        $modelManager->saveWithoutChildren($remotePrefect);

        $this->checkSuccessResponse(
            $this->irController->getRemotes($this->serviceManager->get(RemoteStore::class)),
            [
                $remoteArthur->jsonSerialize(),
                $remoteDent->jsonSerialize(),
                $remoteFord->jsonSerialize(),
                $remotePrefect->jsonSerialize(),
            ],
            4,
        );
    }

    public function testPostButton(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $key = (new Key($this->modelWrapper))
            ->setNames([(new Key\Name($this->modelWrapper))->setName('ford')])
            ->setProtocol(Protocol::NEC)
            ->setAddress(42)
            ->setCommand(24)
        ;
        $modelManager->save($key);
        $event = (new Event($this->modelWrapper))
            ->setName('ford')
            ->setElements([
                (new Event\Element($this->modelWrapper))
                    ->setClass(TestEvent::class)
                    ->setMethod('test')
                    ->setParameters(['arthur' => 'dent']),
            ])
        ;
        $modelManager->save($event);
        $button = (new Remote\Button($this->modelWrapper))
            ->setName('marvin')
            ->setKeys([
                (new Remote\Key($this->modelWrapper))
                    ->setKey($key),
            ])
            ->setEvent($event)
        ;
        $remote = (new Remote($this->modelWrapper))
            ->setName('arthur')
            ->setButtons([$button])
        ;
        $modelManager->save($remote);
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(7)
                ->setName('IR')
                ->setHelper('ir')
        );
        $testEvent = $this->serviceManager->get(TestEvent::class);

        $this->prophesizeWrite(
            $module,
            0,
            chr(2) . chr(0) . chr(42) . chr(0) . chr(24),
        );

        $this->assertEquals('', $testEvent->arthur);
        $this->checkSuccessResponse(
            $this->irController->postButton(
                $this->serviceManager->get(EventService::class),
                $this->serviceManager->get(IrService::class),
                $module,
                $button,
            )
        );
        $this->assertEquals('dent', $testEvent->arthur);
    }

    public function testPostButtonEvent(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $event = (new Event($this->modelWrapper))
            ->setName('ford')
            ->setElements([
                (new Event\Element($this->modelWrapper))
                    ->setClass(TestEvent::class)
                    ->setMethod('test')
                    ->setParameters(['arthur' => 'dent']),
            ])
        ;
        $modelManager->save($event);
        $button = (new Remote\Button($this->modelWrapper))
            ->setName('marvin')
            ->setEvent($event)
        ;
        $remote = (new Remote($this->modelWrapper))
            ->setName('arthur')
            ->setButtons([$button])
        ;
        $modelManager->save($remote);
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(7)
                ->setName('IR')
                ->setHelper('ir')
        );
        $testEvent = $this->serviceManager->get(TestEvent::class);

        $this->assertEquals('', $testEvent->arthur);
        $this->checkSuccessResponse(
            $this->irController->postButton(
                $this->serviceManager->get(EventService::class),
                $this->serviceManager->get(IrService::class),
                $module,
                $button,
            )
        );
        $this->assertEquals('dent', $testEvent->arthur);
    }

    public function testPostButtonKey(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $key = (new Key($this->modelWrapper))
            ->setNames([(new Key\Name($this->modelWrapper))->setName('ford')])
            ->setProtocol(Protocol::NEC)
            ->setAddress(42)
            ->setCommand(24)
        ;
        $modelManager->save($key);
        $button = (new Remote\Button($this->modelWrapper))
            ->setName('marvin')
            ->setKeys([
                (new Remote\Key($this->modelWrapper))
                    ->setKey($key),
            ])
        ;
        $remote = (new Remote($this->modelWrapper))
            ->setName('arthur')
            ->setButtons([$button])
        ;
        $modelManager->save($remote);
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(7)
                ->setName('IR')
                ->setHelper('ir')
        );
        $testEvent = $this->serviceManager->get(TestEvent::class);

        $this->prophesizeWrite(
            $module,
            0,
            chr(2) . chr(0) . chr(42) . chr(0) . chr(24),
        );

        $this->assertEquals('', $testEvent->arthur);
        $this->checkSuccessResponse(
            $this->irController->postButton(
                $this->serviceManager->get(EventService::class),
                $this->serviceManager->get(IrService::class),
                $module,
                $button,
            )
        );
        $this->assertEquals('', $testEvent->arthur);
    }

    public function testPostButtonNone(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $button = (new Remote\Button($this->modelWrapper))
            ->setName('marvin')
        ;
        $remote = (new Remote($this->modelWrapper))
            ->setName('arthur')
            ->setButtons([$button])
        ;
        $modelManager->save($remote);
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(7)
                ->setName('IR')
                ->setHelper('ir')
        );
        $testEvent = $this->serviceManager->get(TestEvent::class);

        $this->assertEquals('', $testEvent->arthur);
        $this->checkSuccessResponse(
            $this->irController->postButton(
                $this->serviceManager->get(EventService::class),
                $this->serviceManager->get(IrService::class),
                $module,
                $button,
            )
        );
        $this->assertEquals('', $testEvent->arthur);
    }
}
