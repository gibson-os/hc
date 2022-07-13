<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Box;

class BoxRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Box::class)] private readonly string $boxTableName)
    {
    }

    public function getFreeUuid(): string
    {
        $table = $this->getTable($this->boxTableName)
            ->setWhere('`uuid`=?')
        ;

        while (true) {
            $uuid = mb_substr(md5((string) mt_rand()), 0, 8);
            $table->setWhereParameters([$uuid]);

            if ($table->selectPrepared() === 0) {
                return $uuid;
            }
        }
    }
}
