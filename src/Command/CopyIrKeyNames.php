<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;
use mysqlDatabase;
use mysqlTable;
use Psr\Log\LoggerInterface;

class CopyIrKeyNames extends AbstractCommand
{
    public function __construct(
        private readonly mysqlDatabase $database,
        private readonly ModelManager $modelManager,
        LoggerInterface $logger,
        #[GetTableName(Key::class)] private readonly string $keyTableName,
    ) {
        parent::__construct($logger);
    }

    protected function run(): int
    {
        $table = new mysqlTable($this->database, $this->keyTableName);
        $table->select();

        do {
            /** @psalm-suppress UndefinedPropertyFetch */
            $this->modelManager->saveWithoutChildren(
                (new Name())
                    ->setName($table->name->getValue() ?? '')
                    ->setKeyId((int) $table->id->getValue())
            );
        } while ($table->next());

        return self::SUCCESS;
    }
}
