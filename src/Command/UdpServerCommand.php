<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\ReceiverService;
use mysqlDatabase;

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
        mysqlDatabase $mysqlDatabase
    ) {
        $this->protocol = $protocol;
        $this->receiverService = $receiverService;
        $this->envService = $envService;
        $this->mysqlDatabase = $mysqlDatabase;

        $this->setArgument('bindIp', true);
    }

    /**
     * @throws GetError
     */
    protected function run(): int
    {
        echo 'Starte Server...' . PHP_EOL;

        while (1) {
            $this->mysqlDatabase->closeDB();
            $this->mysqlDatabase->openDB($this->envService->getString('MYSQL_DATABASE'));

            try {
                $this->receiverService->receive($this->protocol);
            } catch (AbstractException $exception) {
                echo 'Server Error: ' . $exception->getMessage() . PHP_EOL;
            }
        }

        return 0;
    }
}
