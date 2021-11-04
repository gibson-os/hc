<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use Generator;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService as ImageService;
use JsonException;

class ImageStore extends AbstractDatabaseStore
{
    private ?int $slaveId = null;

    protected function getModelClassName(): string
    {
        return Sequence::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`hc_sequence`.`type`=?', [ImageService::SEQUENCE_TYPE]);

        if ($this->slaveId !== null) {
            $this->addWhere('`hc_sequence`.`module_id`=?', [$this->slaveId]);
        }
    }

    protected function initTable(): void
    {
        parent::initTable();

        $this->table->appendJoinLeft(
            '`gibson_os`.`' . Sequence\Element::getTableName() . '`',
            '`hc_sequence`.`id`=`' . Sequence\Element::getTableName() . '`.`sequence_id`'
        );
    }

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
            '`' . Sequence\Element::getTableName() . '`.`data`'
        );

        foreach ($this->table->connection->fetchObjectList() as $sequence) {
            yield [
                'id' => $sequence->id,
                'name' => $sequence->name,
                'leds' => JsonUtility::decode($sequence->data),
            ];
        }
    }

    public function setSlaveId(?int $slaveId): ImageStore
    {
        $this->slaveId = $slaveId;

        return $this;
    }
}
