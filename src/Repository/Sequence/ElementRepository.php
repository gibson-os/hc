<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Sequence;

use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Sequence\Element;

/**
 * @method Element[] fetchAll(string $where, array $parameters, string $abstractModelClassName = AbstractModel::class, int $limit = null, int $offset = null, string $orderBy = null)
 */
class ElementRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     *
     * @return Element[]
     */
    public function getBySequence(int $sequenceId): array
    {
        return $this->fetchAll('`sequence_id`=?', [$sequenceId], Element::class);
    }

    /**
     * @throws DeleteError
     */
    public function deleteBySequence(Sequence $sequence): void
    {
        $table = $this->getTable(Element::getTableName())
            ->setWhere('`sequence_id`=?')
            ->addWhereParameter($sequence->getId())
        ;

        if (!$table->deletePrepared()) {
            throw new DeleteError();
        }
    }
}
