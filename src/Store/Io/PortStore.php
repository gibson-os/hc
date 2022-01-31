<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Service\Slave\IoService as IoService;
use GibsonOS\Module\Hc\Store\AbstractAttributeStore;

class PortStore extends AbstractAttributeStore
{
    protected function getType(): string
    {
        return IoService::ATTRIBUTE_TYPE_PORT;
    }

    protected function getTypeName(): string
    {
        return 'io';
    }

    /**
     * @throws SelectError
     *
     * @return array<int, array<string, string|array<int, string>>>
     */
    public function getList(): array
    {
        $list = [];

        /** @var Attribute $attribute */
        foreach (parent::getList() as $attribute) {
            $subId = $attribute->getSubId() ?? 0;
            $key = $attribute->getKey();

            if (!isset($list[$subId])) {
                $list[$subId] = ['number' => $subId];
            }

            foreach ($attribute->getValues() as $value) {
                if ($key === IoService::ATTRIBUTE_PORT_KEY_VALUE_NAMES) {
                    if (!isset($list[$subId][$key]) || !is_array($list[$subId][$key])) {
                        $list[$subId][$key] = [];
                    }

                    $list[$subId][$key][$value->getOrder()] = $value->getValue();

                    continue;
                }

                $list[$subId][$key] = $value->getValue();
            }
        }

        return $list;
    }
}
