<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\ArgumentError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\ReceiverService;
use mysqlDatabase;
use Psr\Log\LoggerInterface;

class UdpServerCommand extends AbstractCommand
{
    /**
     * @var UdpService
     */
    private $protocol;

    /**
     * @var ReceiverService
     */
    private $receiverService;

    /**
     * @var EnvService
     */
    private $envService;

    /**
     * @var mysqlDatabase
     */
    private $mysqlDatabase;

    public function __construct(
        UdpService $protocol,
        ReceiverService $receiverService,
        EnvService $envService,
        mysqlDatabase $mysqlDatabase,
        LoggerInterface $logger
    ) {
        $this->protocol = $protocol;
        $this->receiverService = $receiverService;
        $this->envService = $envService;
        $this->mysqlDatabase = $mysqlDatabase;

        parent::__construct($logger);

        $this->setArgument('bindIp', false);
    }

    /**
     * @throws GetError
     * @throws ArgumentError
     */
    protected function run(): int
    {
        $this->protocol->setIp($this->getArgument('bindIp') ?? '0');
        $this->logger->info('Start server...');

        while (1) {
            $this->mysqlDatabase->closeDB();
            $this->mysqlDatabase->openDB($this->envService->getString('MYSQL_DATABASE'));

            try {
                $this->receiverService->receive($this->protocol);
            } catch (AbstractException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return 0;
    }
}
