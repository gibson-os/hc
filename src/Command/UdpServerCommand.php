<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\Command\Argument;
use GibsonOS\Core\Attribute\Command\Lock;
use GibsonOS\Core\Attribute\GetEnv;
use GibsonOS\Core\Attribute\Install\Cronjob;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Service\EnvService;
use GibsonOS\Module\Hc\Service\Protocol\UdpService;
use GibsonOS\Module\Hc\Service\ReceiverService;
use JsonException;
use MDO\Client;
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
        private readonly Client $client,
        #[GetEnv('MYSQL_HOST')]
        private readonly string $mysqlHost,
        #[GetEnv('MYSQL_USER')]
        private readonly string $mysqlUser,
        #[GetEnv('MYSQL_PASS')]
        private readonly string $mysqlPassword,
        #[GetEnv('MYSQL_DATABASE')]
        private readonly string $mysqlDatabaseName,
        LoggerInterface $logger,
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
            $this->client->close();
            $this->client->connect($this->mysqlHost, $this->mysqlUser, $this->mysqlPassword);
            $this->client->useDatabase($this->mysqlDatabaseName);

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
