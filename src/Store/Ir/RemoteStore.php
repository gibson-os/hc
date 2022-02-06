<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Ir;

use Generator;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Dto\Ir\Remote;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;

class RemoteStore extends AbstractAttributeStore
{
    protected function getType(): string
    {
        return IrService::ATTRIBUTE_TYPE_REMOTE;
    }

    protected function getTypeName(): string
    {
        return 'ir';
    }

    protected function getDefaultOrder(): string
    {
        return '`' . $this->valueTableName . '`.`value`';
    }

    protected function getOrderMapping(): array
    {
        return ['name' => $this->getDefaultOrder()];
    }

    protected function initTable(): void
    {
        $this->setKeys([IrService::REMOTE_ATTRIBUTE_NAME]);
        parent::initTable();
    }

    /**
     * @throws SelectError
     *
     * @return Generator<Remote>
     */
    public function getList(): iterable
    {
        /** @var Attribute $attribute */
        foreach (parent::getList() as $attribute) {
            yield new Remote($attribute->getValues()[0]->getValue(), $attribute->getSubId());
        }
    }
}
