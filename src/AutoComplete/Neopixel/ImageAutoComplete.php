<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Neopixel;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\AutoCompleteException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\ModelInterface;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService as ImageService;

class ImageAutoComplete implements AutoCompleteInterface
{
    private ModuleRepository $moduleRepository;

    private SequenceRepository $sequenceRepository;

    public function __construct(ModuleRepository $moduleRepository, SequenceRepository $sequenceRepository)
    {
        $this->moduleRepository = $moduleRepository;
        $this->sequenceRepository = $sequenceRepository;
    }

    /**
     * @throws DateTimeError
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
    public function getById($id, array $parameters): Sequence
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

    /**
     * @throws AutoCompleteException
     */
    public function getIdFromModel(ModelInterface $model): int
    {
        if (!$model instanceof Sequence) {
            throw new AutoCompleteException(sprintf('Model is not instance of %s', Sequence::class));
        }

        return $model->getId() ?? 0;
    }
}
