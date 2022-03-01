<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Dto\Io\DirectConnect\Command;
use GibsonOS\Module\Hc\Model\Module;

class DirectConnect implements AttributeInterface
{
    /**
     * @param Module    $module
     * @param Port      $port
     * @param Command[] $commands
     */
    public function __construct(
        private Module $module,
        private Port $port,
        #[IsAttribute()] private array $commands = []
    ) {
    }

    public function getPort(): Port
    {
        return $this->port;
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param Command[] $commands
     */
    public function setCommands(array $commands): DirectConnect
    {
        $this->commands = $commands;

        return $this;
    }

    public function getSubId(): ?int
    {
        return $this->port->getNumber();
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }
}
