<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Factory\ProtocolFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\MasterRepository as MasterRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;

class SenderService extends AbstractService
{
    /**
     * @var MasterFormatter
     */
    private $masterFormatter;

    /**
     * @var TransformService
     */
    private $transformService;

    /**
     * @var MasterRepository
     */
    private $masterRepository;

    /**$data
     * Server constructor.
     */
    public function __construct(
        MasterFormatter $masterFormatter,
        TransformService $transformService,
        MasterRepository $masterRepository
    ) {
        $this->masterFormatter = $masterFormatter;
        $this->transformService = $transformService;
        $this->masterRepository = $masterRepository;
    }

    /**
     * @throws AbstractException
     */
    public function send(Master $master, int $type, string $data)
    {
        $this->getProtocol($master)->send($type, $data, $master->getAddress());
        usleep(1);
    }

    /**
     * @throws FileNotFound
     * @throws ReceiveError
     */
    public function receiveReadData(Master $master, int $type): string
    {
        $protocol = $this->getProtocol($master);
        $data = $protocol->receiveReadData();

        $this->masterFormatter->checksumEqual($data);

        if ($this->masterFormatter->getMasterAddress($data) !== $master->getAddress()) {
            throw new ReceiveError('Master Adresse stimmt nicht überein!');
        }

        if ($this->masterFormatter->getType($data) !== $type) {
            throw new ReceiveError('Typ stimmt nicht überein!');
        }

        return $data;
    }

    /**
     * @throws FileNotFound
     */
    public function receiveReceiveReturn(Master $master): void
    {
        $this->getProtocol($master)->receiveReceiveReturn($master->getAddress());
    }

    /**
     * @throws FileNotFound
     */
    private function getProtocol(Master $master): ProtocolInterface
    {
        return ProtocolFactory::create($master->getProtocol());
    }
}
