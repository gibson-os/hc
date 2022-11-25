<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;
use GibsonOS\Module\Hc\Store\Ir\KeyStore;
use Psr\Log\LoggerInterface;

class CopyIrKeyNames extends AbstractCommand
{
    public function __construct(
        private readonly KeyStore $keyStore,
        private readonly ModelManager $modelManager,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    protected function run(): int
    {
        /** @var Key $key */
        foreach ($this->keyStore->getList() as $key) {
            $this->modelManager->saveWithoutChildren(
                (new Name())
                ->setName($key->getName() ?? '')
                ->setKey($key)
            );
        }

        return self::SUCCESS;
    }
}
