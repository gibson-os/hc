<?php
namespace GibsonOS\Module\Hc\Utility\Formatter;

use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Master;
use GibsonOS\Module\Hc\Service\Server;
use GibsonOS\Module\Hc\Utility\Transform;

abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * @var int|null
     */
    protected $logId;
    /**
     * @var Module
     */
    protected $module;
    /**
     * @var string
     */
    protected $direction;
    /**
     * @var int
     */
    protected $type;
    /**
     * @var string
     */
    protected $data;
    /**
     * @var int|null
     */
    protected $command;

    /**
     * AbstractFormatter constructor.
     * @param Module $module
     * @param string $direction
     * @param int $type
     * @param string $data
     * @param int|null $command
     * @param int|null $logId
     */
    public function __construct(Module $module, $direction, $type, $data, $command = null, $logId = null)
    {
        $this->module = $module;
        $this->direction = $direction;
        $this->type = $type;
        $this->data = $data;
        $this->command = $command;
        $this->logId = $logId;
    }

    /**
     * @return int|null|string
     */
    public function command()
    {
        return $this->command;
    }

    /**
     * @return null|string
     */
    public function render(): ?string
    {
        return null;
    }

    /**
     * @return null|string
     */
    public function text(): ?string
    {
        if ($this->type == Master::TYPE_HANDSHAKE) {
            return 'Adresse ' .
                Transform::hexToInt(substr($this->data, 0, 2)) .
                ' gesendet an ' .
                Transform::hexToAscii(substr($this->data, 2));
        } else if (
            $this->type == Master::TYPE_STATUS &&
            $this->direction === Server::DIRECTION_OUTPUT
        ) {
            return 'Status abfragen';
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function isDefaultType(): bool
    {
        if ($this->type === Master::TYPE_HANDSHAKE) {
            return true;
        }

        if (
            $this->type === Master::TYPE_STATUS &&
            $this->direction === Server::DIRECTION_OUTPUT
        ) {
            return true;
        }

        return false;
    }
}