<?php
declare(strict_types=1);

namespace GibsonOS\Test\Unit\Hc\Repository;

use Codeception\Test\Unit;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Test\Unit\Core\Repository\RepositoryTrait;
use MDO\Dto\Query\Join;
use MDO\Dto\Query\Where;
use MDO\Dto\Table;
use MDO\Query\SelectQuery;
use Psr\Log\LoggerInterface;

class TypeRepositoryTest extends Unit
{
    use RepositoryTrait;

    private TypeRepository $typeRepository;

    protected function _before()
    {
        $this->loadRepository('hc_type');

        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->typeRepository = new TypeRepository(
            $this->repositoryWrapper->reveal(),
            'hc_type',
            'hc_type_default_address',
        );
    }

    public function testGetById(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`id`=?', [42]))
            ->setLimit(1)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Type::class),
            $this->typeRepository->getById(42),
        );
    }

    public function testGetByHelperName(): void
    {
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addWhere(new Where('`helper`=?', ['marvin']))
            ->setLimit(1)
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Type::class),
            $this->typeRepository->getByHelperName('marvin'),
        );
    }

    public function testGetByDefaultAddress(): void
    {
        $defaultAddressTable = new Table('hc_type_default_address', []);
        $this->tableManager->getTable('hc_type_default_address')
            ->shouldBeCalledOnce()
            ->willReturn($defaultAddressTable)
        ;
        $selectQuery = (new SelectQuery($this->table, 't'))
            ->addJoin(new Join($defaultAddressTable, 'da', '`t`.`id`=`da`.`type_id`'))
            ->addWhere(new Where('`da`.`address`=?', [42]))
            ->setLimit(1)
        ;

        $model = $this->loadModel($selectQuery, Type::class);
        $this->repositoryWrapper->getTableManager()
            ->shouldBeCalledTimes(2)
        ;
        $this->repositoryWrapper->getModelWrapper()
            ->shouldBeCalledOnce()
        ;

        $this->assertEquals(
            $model,
            $this->typeRepository->getByDefaultAddress(42),
        );
    }

    /**
     * @dataProvider getData
     */
    public function testFindByName(
        string $where,
        array $parameters,
        bool $hcSlave = null,
        string $network = null,
    ): void {
        $selectQuery = (new SelectQuery($this->table))
            ->addWhere(new Where($where, $parameters))
        ;

        $this->assertEquals(
            $this->loadModel($selectQuery, Type::class, ''),
            $this->typeRepository->findByName('marvin', $hcSlave, $network)[0],
        );
    }

    public function getData(): array
    {
        return [
            'simple' => ['`name` LIKE ?', ['marvin%']],
            'hc slave' => ['`name` LIKE ? AND `is_hc_slave`=?', ['marvin%', 1], true],
            'network' => ['`name` LIKE ? AND `network`=?', ['marvin%', 'galaxy'], null, 'galaxy'],
            'all' => ['`name` LIKE ? AND `is_hc_slave`=? AND `network`=?', ['marvin%', 0, 'galaxy'], false, 'galaxy'],
        ];
    }
}
