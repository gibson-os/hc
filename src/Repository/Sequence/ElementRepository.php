<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Sequence;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Sequence\Element;

class ElementRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Element::class)] private string $elementTableName)
    {
    }

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
        $table = $this->getTable($this->elementTableName)
            ->setWhere('`sequence_id`=?')
            ->addWhereParameter($sequence->getId())
        ;

        if (!$table->deletePrepared()) {
            throw new DeleteError();
        }
    }
}
