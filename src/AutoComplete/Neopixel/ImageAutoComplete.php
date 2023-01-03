<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Neopixel;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Repository\Neopixel\ImageRepository;

class ImageAutoComplete implements AutoCompleteInterface
{
    public function __construct(private readonly ImageRepository $imageRepository)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        return $this->imageRepository->findByName((int) $parameters['moduleId'], $namePart . '*');
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Image
    {
        return $this->imageRepository->getById((int) $parameters['moduleId'], (int) $id);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.neopixel.model.Image';
    }

    public function getValueField(): string
    {
        return 'id';
    }

    public function getDisplayField(): string
    {
        return 'name';
    }
}
