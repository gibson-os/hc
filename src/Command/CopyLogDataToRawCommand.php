<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use MDO\Client;
use MDO\Dto\Query\Where;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use MDO\Manager\TableManager;
use MDO\Query\SelectQuery;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * @description TEMP copy old log data to new raw field
 */
class CopyLogDataToRawCommand extends AbstractCommand
{
    public function __construct(
        LoggerInterface $logger,
        private readonly Client $client,
        private readonly TransformService $transformService,
        private readonly ModelManager $modelManager,
        private readonly TableManager $tableManager,
        private readonly ModelWrapper $modelWrapper,
        #[GetTableName(Log::class)]
        private readonly string $logTableName,
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws ClientException
     * @throws RecordException
     */
    protected function run(): int
    {
        $selectQuery = (new SelectQuery($this->tableManager->getTable($this->logTableName)))
            ->addWhere(new Where('`data`!=""', []))
            ->addWhere(new Where('`raw_data`=""', []))
        ;
        $result = $this->client->execute($selectQuery);

        foreach ($result->iterateRecords() as $record) {
            $log = new Log($this->modelWrapper);
            $this->modelManager->loadFromRecord($record, $log);
            $log->setRawData($this->transformService->hexToAscii($log->getData()));
            $this->logger->info(sprintf(
                'Transform #%d data from "%s" to "%s"',
                $log->getId() ?? 0,
                $log->getData(),
                $log->getRawData(),
            ));
            $this->modelManager->save($log);
        }

        return self::SUCCESS;
    }
}
