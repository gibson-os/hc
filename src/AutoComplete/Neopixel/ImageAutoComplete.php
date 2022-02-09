<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Neopixel;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService;

class ImageAutoComplete implements AutoCompleteInterface
{
    public function __construct(private ModuleRepository $moduleRepository, private SequenceRepository $sequenceRepository)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->sequenceRepository->findByName(
            $this->moduleRepository->getById((int) $parameters['moduleId']),
            $namePart . '*',
            ImageService::SEQUENCE_TYPE
        );
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Sequence
    {
        return $this->sequenceRepository->getById((int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.neopixel.model.Image';
    }

    public function getParameters(): array
    {
        return [];
    }
}
