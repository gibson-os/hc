<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Io;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;

class PortAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly PortRepository $portRepository)
    {
    }

    /**
     * @throws SelectError
     *
     * @return Port[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->portRepository->findByName((int) $parameters['moduleId'], $namePart . '*');
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Port
    {
        return $this->portRepository->getById((int) $parameters['moduleId'], (int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.neopixel.model.Port';
    }

    public function getParameters(): array
    {
        return [];
    }
}
