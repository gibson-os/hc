<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Sequence;

use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Sequence\Element;

class ElementRepository extends AbstractRepository
{
    /**
     * @throws SelectError
     * @throws DateTimeError
     *
     * @return Element[]
     */
    public function getBySequence(int $sequenceId): array
    {
        $table = $this->getTable(Element::getTableName())
            ->setWhere('`sequence_id`=?')
            ->addWhereParameter($sequenceId)
        ;

        $select = $table->select();

        if ($select === false) {
            throw (new SelectError())->setTable($table);
        }

        if ($select === 0) {
            return [];
        }

        $models = [];

        do {
            $model = new Element();
            $model->loadFromMysqlTable($table);

            $models[] = $model;
        } while ($table->next());

        return $models;
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
