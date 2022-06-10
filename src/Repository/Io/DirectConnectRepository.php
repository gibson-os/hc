<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Io;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;

class DirectConnectRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(DirectConnect::class)] private readonly string $directConnectTableName)
    {
    }

    public function updateOrder(DirectConnect $directConnect): void
    {
        $table = $this->getTable($this->directConnectTableName);
        $table
            ->setWhere('`input_port_id`=? AND `order`>?')
            ->setWhereParameters([$directConnect->getInputPortId(), $directConnect->getOrder()])
            ->update('`order`=`order`-1')
        ;
    }

    public function getByOrder(Port $inputPort, int $order): DirectConnect
    {
        return $this->fetchOne(
            '`input_port_id`=? AND `order`=?',
            [$inputPort->getId() ?? 0, $order],
            DirectConnect::class
        );
    }
}
