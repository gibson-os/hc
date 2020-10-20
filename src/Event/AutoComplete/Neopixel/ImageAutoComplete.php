<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\AutoComplete\Neopixel;

use GibsonOS\Core\Event\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\ModelInterface;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService as ImageService;

class ImageAutoComplete implements AutoCompleteInterface
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var SequenceRepository
     */
    private $sequenceRepository;

    public function __construct(ModuleRepository $moduleRepository, SequenceRepository $sequenceRepository)
    {
        $this->moduleRepository = $moduleRepository;
        $this->sequenceRepository = $sequenceRepository;
    }

    /**
     * @throws DateTimeError
     * @throws GetError
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
     * @param int $id
     *
     * @throws SelectError
     */
    public function getById($id, array $parameters): ModelInterface
    {
        return $this->sequenceRepository->getById($id);
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
