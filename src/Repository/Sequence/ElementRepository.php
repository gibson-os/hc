<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Sequence;

use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Sequence\Element as ElementModel;

class ElementRepository extends AbstractRepository
{
    /**
     * @throws DeleteError
     */
    public function deleteBySequence(Sequence $sequence)
    {
        $table = $this->getTable(ElementModel::getTableName());
        $table->setWhere('`sequence_id`=' . $this->escape((string) $sequence->getId()));

        if (!$table->delete()) {
            throw new DeleteError();
        }
    }
}
