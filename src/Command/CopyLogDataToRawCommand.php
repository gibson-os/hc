<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use mysqlDatabase;
use mysqlTable;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * @description TEMP copy old log data to new raw field
 */
class CopyLogDataToRawCommand extends AbstractCommand
{
    public function __construct(
        LoggerInterface $logger,
        private mysqlDatabase $mysqlDatabase,
        private TransformService $transformService,
        private ModelManager $modelManager,
        #[GetTableName(Log::class)]
        private string $logTableName
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws GetError
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
     */
    protected function run(): int
    {
        $logTable = new mysqlTable($this->mysqlDatabase, $this->logTableName);
        $logTable->setWhere('`data`!="" AND `raw_data`=""');

        if (!$logTable->select()) {
            return self::ERROR;
        }

        do {
            $log = new Log($this->mysqlDatabase);
            $this->modelManager->loadFromMysqlTable($logTable, $log);
            $log->setRawData($this->transformService->hexToAscii($log->getData()));
            $this->logger->info(sprintf(
                'Transform #%d data from "%s" to "%s"',
                $log->getId() ?? 0,
                $log->getData(),
                $log->getRawData()
            ));
            $this->modelManager->save($log);
        } while ($logTable->next());

        return self::SUCCESS;
    }
}
