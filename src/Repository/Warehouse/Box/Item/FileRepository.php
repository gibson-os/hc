<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse\Box\Item;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item\File;

class FileRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(File::class)] private readonly string $fileTableName)
    {
    }

    /**
     * @param File[] $files
     *
     * @throws SelectError
     *
     * @return File[]
     */
    public function getFilesNotIn(Item $item): array
    {
        $files = $item->getFiles();
        $table = $this->getTable($this->fileTableName);
        $parameters = array_map(fn (File $file) => $file->getId(), $files);
        $parameters[] = $item->getId();

        return $this->fetchAll(
            (count($files) ? '`id` NOT IN (' . $table->getParametersString($files) . ') AND ' : '') . '`item_id`=?',
            $parameters,
            File::class
        );
    }
}
