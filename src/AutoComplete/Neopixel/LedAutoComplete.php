<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Neopixel;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\Neopixel\LedRepository;

class LedAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly LedRepository $ledRepository)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->ledRepository->findByNumber((int) $parameters['moduleId'], $namePart . '*');
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Led
    {
        return $this->ledRepository->getById((int) $parameters['moduleId'], (int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.neopixel.model.Led';
    }
}
