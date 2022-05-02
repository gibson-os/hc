<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Io;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;

class PortAutoComplete implements AutoCompleteInterface
{
    public function __construct(private ModuleRepository $moduleRepository, private ValueRepository $valueRepository)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $slave = $this->getSlave($parameters);

        try {
            $ports = $this->valueRepository->findAttributesByValue(
                $namePart . '*',
                $slave->getTypeId(),
                [IoService::ATTRIBUTE_PORT_KEY_NAME],
                [$slave->getId()],
                true,
                IoService::ATTRIBUTE_TYPE_PORT
            );
        } catch (SelectError) {
            $ports = [];
        }
        errlog($ports);

        return $ports;
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Value
    {
        $slave = $this->getSlave($parameters);

        $values = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            (int) $id,
            [$slave->getId() ?? 0],
            IoService::ATTRIBUTE_TYPE_PORT
        );

        return reset($values);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.io.model.Port';
    }

    /**
     * @throws SelectError
     */
    private function getSlave(array $parameters): Module
    {
        return $this->moduleRepository->getById((int) $parameters['moduleId']);
    }
}
