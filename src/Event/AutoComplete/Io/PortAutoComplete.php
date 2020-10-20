<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event\AutoComplete\Io;

use GibsonOS\Core\Event\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\ModelInterface;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;

class PortAutoComplete implements AutoCompleteInterface
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var ValueRepository
     */
    private $valueRepository;

    public function __construct(ModuleRepository $moduleRepository, ValueRepository $valueRepository)
    {
        $this->moduleRepository = $moduleRepository;
        $this->valueRepository = $valueRepository;
    }

    public function getByNamePart(string $namePart, array $parameters): array
    {
        $slave = $this->getSlave($parameters);

        try {
            $valueRepository = new ValueRepository();
            $ports = $valueRepository->findAttributesByValue(
                $namePart . '*',
                $slave->getTypeId(),
                [IoService::ATTRIBUTE_PORT_KEY_NAME],
                [$slave->getId()],
                null,
                IoService::ATTRIBUTE_TYPE_PORT
            );
        } catch (SelectError $e) {
            $ports = [];
        }

        return $ports;
    }

    public function getById($id, array $parameters): ModelInterface
    {
        $slave = $this->getSlave($parameters);

        $values = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            $id,
            [(int) $slave->getId()],
            IoService::ATTRIBUTE_TYPE_PORT
        );

        return reset($values);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.io.model.Port';
    }

    public function getParameters(): array
    {
        return [];
    }

    /**
     * @throws DateTimeError
     * @throws GetError
     * @throws SelectError
     */
    private function getSlave(array $parameters): Module
    {
        return $this->moduleRepository->getById((int) $parameters['moduleId']);
    }
}
