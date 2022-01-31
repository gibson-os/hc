<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use Psr\Log\LoggerInterface;

class TmpCopyIrKeysCommand extends AbstractCommand
{
    public function __construct(
        private ValueRepository $valueRepository,
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

        foreach ($this->valueRepository->getByTypeId(3, type: 'irKey') as $value) {
            $attribute = $value->getAttribute();
            $subId = $attribute->getSubId() ?? 0;

            if (!isset($keys[$subId])) {
                $keys[$subId] = [];
            }

            $keys[$subId][$attribute->getKey()] = $value->getValue();
        }

        foreach ($keys as $key) {
            $newSubId = 0;
            $name = '';

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
