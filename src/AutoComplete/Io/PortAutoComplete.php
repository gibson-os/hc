<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Io;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;

class PortAutoComplete implements AutoCompleteInterface
{
    private ModuleRepository $moduleRepository;

    private ValueRepository $valueRepository;

    public function __construct(ModuleRepository $moduleRepository, ValueRepository $valueRepository)
    {
        $this->moduleRepository = $moduleRepository;
        $this->valueRepository = $valueRepository;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
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

    /**
     * @param $id
     *
     * @throws DateTimeError
     * @throws SelectError
     */
    public function getById($id, array $parameters): Value
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

    /**
     * @throws DateTimeError
     * @throws SelectError
     */
    private function getSlave(array $parameters): Module
    {
        return $this->moduleRepository->getById((int) $parameters['moduleId']);
    }
}
