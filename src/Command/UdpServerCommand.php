<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\Command\Argument;
use GibsonOS\Core\Attribute\Command\Lock;
use GibsonOS\Core\Attribute\Install\Cronjob;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\ReceiverService;
use JsonException;
use mysqlDatabase;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * @description Run HC UDP Server
 */
#[Lock('hcUdpServerCommand')]
#[Cronjob]
class UdpServerCommand extends AbstractCommand
{
    #[Argument('Run Server on IP')]
    private string $bindIp = '0';

    public function __construct(
        private readonly UdpService $protocol,
        private readonly ReceiverService $receiverService,
        private readonly EnvService $envService,
        private readonly mysqlDatabase $mysqlDatabase,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws GetError
     * @throws JsonException
     * @throws ReflectionException
     */
    protected function run(): int
    {
        $this->protocol->setIp($this->bindIp);
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

        return self::SUCCESS;
    }

    public function setBindIp(string $bindIp): UdpServerCommand
    {
        $this->bindIp = $bindIp;

        return $this;
    }
}
