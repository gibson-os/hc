<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use Psr\Log\LoggerInterface;

class TmpCopyIrKeysCommand extends AbstractCommand
{
    public function __construct(
        private AttributeRepository $attributeRepository,
        private TypeRepository $typeRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws SaveError
     */
    protected function run(): int
    {
        $keys = [];
        $attributes = $this->attributeRepository->getByType(
            $this->typeRepository->getByHelperName('ethbridge'),
            type: 'irKey'
        );

        foreach ($attributes as $attribute) {
            $subId = $attribute->getSubId() ?? 0;

            if (!isset($keys[$subId])) {
                $keys[$subId] = [];
            }

            $values = $attribute->getValues();

            if (count($values) === 0) {
                $this->logger->warning(sprintf(
                    'SubId %d has no value for key "%s"',
                    $subId,
                    $attribute->getKey()
                ));

                continue;
            }

            $keys[$subId][$attribute->getKey()] = $values[0]->getValue();
        }

        foreach ($keys as $oldSubId => $key) {
            $newSubId = 0;
            $name = '';

            if (count($key) !== 4) {
                $this->logger->warning(sprintf(
                    'SubId %d has not all required properties. Found: %s',
                    $oldSubId,
                    implode(', ', array_keys($key))
                ));

                continue;
            }

            foreach ($key as $keyName => $keyValue) {
                $newSubId += match ($keyName) {
                    'protocol' => ((int) $keyValue) << 32,
                    'address' => ((int) $keyValue) << 16,
                    'command' => (int) $keyValue,
                    default => 0
                };

                if ($keyName === 'name') {
                    $name = $keyValue;
                }
            }

            $attribute = (new Attribute())
                ->setKey(IrService::KEY_ATTRIBUTE_NAME)
                ->setSubId($newSubId)
                ->setType(IrService::ATTRIBUTE_TYPE_KEY)
                ->setTypeId(7)
            ;
            $attribute->save();

            (new Attribute\Value())
                ->setAttribute($attribute)
                ->setValue($name)
                ->save()
            ;
        }

        return self::SUCCESS;
    }
}
