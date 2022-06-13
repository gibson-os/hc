<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Io;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;

class DirectConnectRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(DirectConnect::class)] private readonly string $directConnectTableName)
    {
    }

    public function updateOrder(DirectConnect $directConnect): void
    {
        $this->getTable($this->directConnectTableName)
            ->setWhere('`input_port_id`=? AND `order`>?')
            ->setWhereParameters([$directConnect->getInputPortId(), $directConnect->getOrder()])
            ->update('`order`=`order`-1')
        ;
    }

    /**
     * @throws SelectError
     */
    public function getByOrder(Port $inputPort, int $order): DirectConnect
    {
        return $this->fetchOne(
            '`input_port_id`=? AND `order`=?',
            [$inputPort->getId() ?? 0, $order],
            DirectConnect::class
        );
    }

    public function deleteByInputPort(Module $module, Port $port): void
    {
        $this->getTable($this->directConnectTableName)
            ->setWhere('`module_id`=? AND `input_port_id`=?')
            ->setWhereParameters([$module->getId(), $port->getId()])
            ->delete()
        ;
    }
}
