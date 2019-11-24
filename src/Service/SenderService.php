<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Factory\ProtocolFactory;
use GibsonOS\Module\Hc\Model\Master;
use GibsonOS\Module\Hc\Repository\Master as MasterRepository;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Service\Protocol\ProtocolInterface;

class SenderService extends AbstractService
{
    /**
     * @var MasterFormatter
     */
    private $formatter;

    /**
     * @var TransformService
     */
    private $transform;

    /**
     * @var MasterRepository
     */
    private $masterRepository;

    /**$data
     * Server constructor.
     */
    public function __construct(
        MasterFormatter $formatter,
        TransformService $transform,
        MasterRepository $masterRepository
    ) {
        $this->formatter = $formatter;
        $this->transform = $transform;
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

        $this->formatter->checksumEqual($data);

        if ($this->formatter->getMasterAddress($data) !== $master->getAddress()) {
            throw new ReceiveError('Master Adresse stimmt nicht überein!');
        }

        if ($this->formatter->getType($data) !== $type) {
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
