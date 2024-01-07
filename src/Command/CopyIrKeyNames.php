<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;
use JsonException;
use MDO\Client;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use MDO\Manager\TableManager;
use MDO\Query\SelectQuery;
use Psr\Log\LoggerInterface;
use ReflectionException;

class CopyIrKeyNames extends AbstractCommand
{
    public function __construct(
        private readonly ModelWrapper $modelWrapper,
        private readonly ModelManager $modelManager,
        private readonly Client $client,
        private readonly TableManager $tableManager,
        LoggerInterface $logger,
        #[GetTableName(Key::class)]
        private readonly string $keyTableName,
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws SaveError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    protected function run(): int
    {
        $selectQuery = new SelectQuery($this->tableManager->getTable($this->keyTableName));
        $result = $this->client->execute($selectQuery);

        foreach ($result->iterateRecords() as $record) {
            $this->modelManager->saveWithoutChildren(
                (new Name($this->modelWrapper))
                    ->setName((string) ($record->get('name')->getValue() ?? ''))
                    ->setKeyId((int) $record->get('id')->getValue()),
            );
        }

        return self::SUCCESS;
    }
}
