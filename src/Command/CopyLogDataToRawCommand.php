<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\TransformService;
use mysqlDatabase;
use mysqlTable;
use Psr\Log\LoggerInterface;

class CopyLogDataToRawCommand extends AbstractCommand
{
    public function __construct(LoggerInterface $logger, private mysqlDatabase $mysqlDatabase, private TransformService $transformService)
    {
        parent::__construct($logger);
    }

    protected function run(): int
    {
        $logTable = new mysqlTable($this->mysqlDatabase, Log::getTableName());
        $logTable->setWhere('`data`!="" AND `raw_data`=""');

        if (!$logTable->select()) {
            return 1;
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

        return 0;
    }
}
