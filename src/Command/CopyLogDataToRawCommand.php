<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use mysqlDatabase;
use mysqlTable;
use Psr\Log\LoggerInterface;

/**
 * @description TEMP copy old log data to new raw field
 */
class CopyLogDataToRawCommand extends AbstractCommand
{
    public function __construct(
        LoggerInterface $logger,
        private mysqlDatabase $mysqlDatabase,
        private TransformService $transformService,
        #[GetTableName(Log::class)] private string $logTableName
    ) {
        parent::__construct($logger);
    }

    protected function run(): int
    {
        $logTable = new mysqlTable($this->mysqlDatabase, $this->logTableName);
        $logTable->setWhere('`data`!="" AND `raw_data`=""');

        if (!$logTable->select()) {
            return self::ERROR;
        }

        do {
            $log = new Log($this->mysqlDatabase);
            $log->loadFromMysqlTable($logTable);
            $log->setRawData($this->transformService->hexToAscii($log->getData()));
            $this->logger->info(sprintf(
                'Transform #%d data from "%s" to "%s"',
                $log->getId() ?? 0,
                $log->getData(),
                $log->getRawData()
            ));
            $log->save();
        } while ($logTable->next());

        return self::SUCCESS;
    }
}
