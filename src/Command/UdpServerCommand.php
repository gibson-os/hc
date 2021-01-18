<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\ArgumentError;
use GibsonOS\Core\Exception\Flock\LockError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Core\Service\LockService;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\ReceiverService;
use mysqlDatabase;
use Psr\Log\LoggerInterface;

class UdpServerCommand extends AbstractCommand
{
    private const LOCK_NAME = 'hcUdpServer';

    private UdpService $protocol;

    private ReceiverService $receiverService;

    private EnvService $envService;

    private mysqlDatabase $mysqlDatabase;

    private LockService $lockService;

    public function __construct(
        UdpService $protocol,
        ReceiverService $receiverService,
        EnvService $envService,
        mysqlDatabase $mysqlDatabase,
        LockService $lockService,
        LoggerInterface $logger
    ) {
        $this->protocol = $protocol;
        $this->receiverService = $receiverService;
        $this->envService = $envService;
        $this->mysqlDatabase = $mysqlDatabase;
        $this->lockService = $lockService;

        $this->setArgument('bindIp', false);
        $this->setOption('force');

        parent::__construct($logger);
    }

    /**
     * @throws ArgumentError
     * @throws GetError
     */
    protected function run(): int
    {
        try {
            if ($this->hasOption('force')) {
                $this->lockService->forceLock(self::LOCK_NAME);
            } else {
                $this->lockService->lock(self::LOCK_NAME);
            }
        } catch (LockError $e) {
            $this->logger->info('Server already runs!');

            return 1;
        }

        $this->protocol->setIp($this->getArgument('bindIp') ?? '0');
        $this->logger->info('Start server...');

        while (1) {
            $this->mysqlDatabase->closeDB();
            $this->mysqlDatabase->openDB($this->envService->getString('MYSQL_DATABASE'));

            try {
                $this->receiverService->receive($this->protocol);
            } catch (AbstractException $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }

        return 0;
    }
}
