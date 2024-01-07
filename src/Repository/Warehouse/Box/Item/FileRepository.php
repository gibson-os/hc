<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Warehouse\Box\Item;

use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item\File;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class FileRepository extends AbstractRepository
{
    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return File[]
     */
    public function getFilesNotIn(Item $item): array
    {
        $files = $item->getFiles();
        $parameters = array_map(fn (File $file) => $file->getId(), $files);
        $parameters[] = $item->getId();

        return $this->fetchAll(
            (count($files) ? '`id` NOT IN (' . $this->getRepositoryWrapper()->getSelectService()->getParametersString($files) . ') AND ' : '') . '`item_id`=?',
            $parameters,
            File::class,
        );
    }
}
