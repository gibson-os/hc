<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\MiddlewareService;
use GibsonOS\Module\Hc\Controller\IoDirectConnectController;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Module\IoService;
use GibsonOS\Module\Hc\Store\Io\DirectConnectStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;
use MDO\Client;
use MDO\Dto\Query\Where;
use MDO\Dto\Record;
use MDO\Manager\TableManager;
use MDO\Query\SelectQuery;

class IoDirectConnectControllerTest extends HcFunctionalTest
{
    private IoDirectConnectController $ioDirectConnectController;

    protected function _before(): void
    {
        parent::_before();

        $middlewareService = $this->prophesize(MiddlewareService::class);
        $this->serviceManager->setService(MiddlewareService::class, $middlewareService->reveal());

        $this->ioDirectConnectController = $this->serviceManager->get(IoDirectConnectController::class);
    }

    public function testGet(): void
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $portMarvin = (new Port($this->modelWrapper))
            ->setName('marvin')
            ->setModule($module)
            ->setNumber(2)
        ;
        $modelManager->saveWithoutChildren($portMarvin);
        $directConnectArthur = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $modelManager->saveWithoutChildren($directConnectArthur);
        $directConnectDent = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portDent)
            ->setOutputPort($portArthur)
        ;
        $modelManager->saveWithoutChildren($directConnectDent);
        $directConnectDent2 = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portDent)
            ->setOutputPort($portMarvin)
        ;
        $modelManager->saveWithoutChildren($directConnectDent2);
        $this->prophesizeRead($module, 136, 1, chr(0));

        $response = $this->ioDirectConnectController->get(
            $this->serviceManager->get(IoService::class),
            $this->serviceManager->get(DirectConnectStore::class),
            $module,
        );

        $this->checkSuccessResponse(
            $response,
            json_decode(json_encode([
                $directConnectArthur,
                $directConnectDent,
                $directConnectDent2,
            ]), true),
        );
    }

    public function testPostNew()
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $directConnect = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $this->prophesizeWrite(
            $module,
            129,
            chr(16) . chr(146) . chr(0) . chr(1) . chr(3) . chr(0),
        );

        $this->checkSuccessResponse(
            $this->ioDirectConnectController->post(
                $this->serviceManager->get(IoService::class),
                $module,
                $directConnect,
            )
        );

        /** @var Client $client */
        $client = $this->serviceManager->get(Client::class);
        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());
    }

    public function testPostExists()
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $directConnect = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $modelManager->saveWithoutChildren($directConnect);
        $this->prophesizeWrite(
            $module,
            130,
            chr(16) . chr(146) . chr(1) . chr(0) . chr(0) . chr(3) . chr(0),
        );

        /** @var Client $client */
        $client = $this->serviceManager->get(Client::class);
        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());

        $directConnect
            ->setInputPort($portDent)
            ->setOutputPort($portArthur)
        ;

        $this->checkSuccessResponse(
            $this->ioDirectConnectController->post(
                $this->serviceManager->get(IoService::class),
                $module,
                $directConnect,
            )
        );

        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portDent->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portArthur->getId(), $record->get('output_port_id')->getValue());
    }

    public function testDelete()
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $directConnect = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $modelManager->saveWithoutChildren($directConnect);
        $this->prophesizeWrite(
            $module,
            131,
            chr(16) . chr(146) . chr(0) . chr(0),
        );

        /** @var Client $client */
        $client = $this->serviceManager->get(Client::class);
        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());

        $this->checkSuccessResponse(
            $this->ioDirectConnectController->delete(
                $this->serviceManager->get(IoService::class),
                $module,
                $directConnect,
            )
        );

        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );

        $this->assertNull($result->iterateRecords()->current());
    }

    public function testDeleteReset()
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $directConnect = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $modelManager->saveWithoutChildren($directConnect);
        $this->prophesizeWrite(
            $module,
            132,
            chr(16) . chr(146) . chr(0),
        );

        /** @var Client $client */
        $client = $this->serviceManager->get(Client::class);
        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());

        $this->checkSuccessResponse(
            $this->ioDirectConnectController->deleteReset(
                $this->serviceManager->get(IoService::class),
                $module,
                $portArthur,
            )
        );

        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );

        $this->assertNull($result->iterateRecords()->current());
    }

    public function testDeleteResetOtherPort()
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $directConnect = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $modelManager->saveWithoutChildren($directConnect);
        $this->prophesizeWrite(
            $module,
            132,
            chr(16) . chr(146) . chr(1),
        );

        /** @var Client $client */
        $client = $this->serviceManager->get(Client::class);
        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());

        $this->checkSuccessResponse(
            $this->ioDirectConnectController->deleteReset(
                $this->serviceManager->get(IoService::class),
                $module,
                $portDent,
            )
        );

        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());
    }

    public function testGetRead()
    {
        $module = $this->addModule(
            (new Type($this->modelWrapper))
                ->setId(255)
                ->setName('I/O')
                ->setHelper('io'),
        );
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $portArthur = (new Port($this->modelWrapper))
            ->setName('arthur')
            ->setModule($module)
            ->setNumber(0)
        ;
        $modelManager->saveWithoutChildren($portArthur);
        $portDent = (new Port($this->modelWrapper))
            ->setName('dent')
            ->setModule($module)
            ->setNumber(1)
        ;
        $modelManager->saveWithoutChildren($portDent);
        $directConnect = (new DirectConnect($this->modelWrapper))
            ->setInputPort($portArthur)
            ->setOutputPort($portDent)
        ;
        $modelManager->saveWithoutChildren($directConnect);
        $this->prophesizeWrite(
            $module,
            132,
            chr(16) . chr(146) . chr(1),
        );

        /** @var Client $client */
        $client = $this->serviceManager->get(Client::class);
        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());

        $this->checkSuccessResponse(
            $this->ioDirectConnectController->getRead(
                $this->serviceManager->get(IoService::class),
                $module,
                $portDent,
                0,
                false,
            )
        );

        $result = $client->execute(
            (new SelectQuery($this->serviceManager->get(TableManager::class)->getTable($directConnect->getTableName())))
                ->addWhere(new Where('`id`=?', [$directConnect->getId()])),
        );
        /** @var Record $record */
        $record = $result->iterateRecords()->current();

        $this->assertEquals($portArthur->getId(), $record->get('input_port_id')->getValue());
        $this->assertEquals($portDent->getId(), $record->get('output_port_id')->getValue());
    }
}
