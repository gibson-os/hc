<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Sequence;

use GibsonOS\Core\Exception\Repository\DeleteError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Model\Sequence\Element as ElementModel;

class Element extends AbstractRepository
{
    /**
     * @param Sequence $sequence
     *
     * @throws DeleteError
     */
    public static function deleteBySequence(Sequence $sequence)
    {
        $table = self::getTable(ElementModel::getTableName());
        $table->setWhere('`sequence_id`=' . self::escape((string) $sequence->getId()));

        if (!$table->delete()) {
            throw new DeleteError();
        }
    }
}
