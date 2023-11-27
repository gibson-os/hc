<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Controller\IrController;
use GibsonOS\Module\Hc\Dto\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Ir\KeyRepository;
use GibsonOS\Module\Hc\Service\Module\IrService;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;

class IrControllerTest extends HcFunctionalTest
{
    private IrController $irController;

    protected function _before(): void
    {
        parent::_before();

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
}
