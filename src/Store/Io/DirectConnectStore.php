<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use Generator;
use GibsonOS\Core\Dto\Model\ChildrenMapping;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use JsonException;
use MDO\Enum\OrderDirection;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

/**
 * @extends AbstractDatabaseStore<Port>
 *
 * @method Generator<DirectConnect> getList()
 */
class DirectConnectStore extends AbstractDatabaseStore
{
    protected Module $module;

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    protected function getAlias(): ?string
    {
        return 'p';
    }

    protected function getExtends(): array
    {
        return [new ChildrenMapping('directConnects', 'direct_connect_', 'dc', [
            new ChildrenMapping('outputPort', 'output_port_', 'op'),
        ])];
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Generator<DirectConnect>
     */
    protected function getModels(): iterable
    {
        foreach (parent::getModels() as $port) {
            foreach ($port->getDirectConnects() as $directConnect) {
                yield $directConnect;
            }
        }
    }

    protected function getModelClassName(): string
    {
        return Port::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`p`.`module_id`=?', [$this->module->getId()]);
    }

    //    protected function getDefaultOrder(): array
    //    {
    //        return [
    //            '`p`.`number`' => OrderDirection::ASC,
    //            '`dc`.`order`' => OrderDirection::ASC,
    //        ];
    //    }
}
