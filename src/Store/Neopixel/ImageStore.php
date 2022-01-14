<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use Generator;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService;
use GibsonOS\Module\Hc\Store\AbstractSequenceStore;
use JsonException;

class ImageStore extends AbstractSequenceStore
{
    /**
     * @throws JsonException
     */
    public function getList(): Generator
    {
        $this->initTable();
        $this->table->setOrderBy('`hc_sequence`.`name` ASC');

        $this->table->selectPrepared(
            false,
            '`hc_sequence`.`id`, ' .
            '`hc_sequence`.`name`, ' .
            '`' . $this->elementTableName . '`.`data`'
        );

        foreach ($this->table->connection->fetchObjectList() as $sequence) {
            yield [
                'id' => $sequence->id,
                'name' => $sequence->name,
                'leds' => JsonUtility::decode($sequence->data),
            ];
        }
    }

    protected function loadElements(): bool
    {
        return true;
    }

    protected function getType(): int
    {
        return ImageService::SEQUENCE_TYPE;
    }
}
