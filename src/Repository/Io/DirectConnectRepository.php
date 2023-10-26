<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Io;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use JsonException;
use MDO\Dto\Query\Where;
use MDO\Dto\Value;
use MDO\Enum\ValueType;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use MDO\Query\DeleteQuery;
use MDO\Query\UpdateQuery;
use ReflectionException;

class DirectConnectRepository extends AbstractRepository
{
    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(DirectConnect::class)]
        private readonly string $directConnectTableName,
    ) {
        parent::__construct($repositoryWrapper);
    }

    /**
     * @throws ClientException
     */
    public function updateOrder(DirectConnect $directConnect): void
    {
        $updateQuery = (new UpdateQuery(
            $this->getTable($this->directConnectTableName),
            ['order' => new Value('`order`-1', ValueType::FUNCTION)],
        ))
            ->addWhere(new Where('`input_port_id`=?', [$directConnect->getInputPortId()]))
            ->addWhere(new Where('`order`>?', [$directConnect->getOrder()]))
        ;

        $this->getRepositoryWrapper()->getClient()->execute($updateQuery);
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getByOrder(Port $inputPort, int $order): DirectConnect
    {
        return $this->fetchOne(
            '`input_port_id`=? AND `order`=?',
            [$inputPort->getId() ?? 0, $order],
            DirectConnect::class
        );
    }

    public function deleteByInputPort(Port $port): bool
    {
        $deleteQuery = (new DeleteQuery($this->getTable($this->directConnectTableName)))
            ->addWhere(new Where('`input_port_id`=?', [$port->getId()]))
        ;

        try {
            $this->getRepositoryWrapper()->getClient()->execute($deleteQuery);
        } catch (ClientException) {
            return false;
        }

        return true;
    }
}
