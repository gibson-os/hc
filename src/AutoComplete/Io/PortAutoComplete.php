<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Io;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ObjectMapper;
use GibsonOS\Module\Hc\Dto\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\IoService;

class PortAutoComplete implements AutoCompleteInterface
{
    public function __construct(
        private ModuleRepository $moduleRepository,
        private ValueRepository $valueRepository,
        private ObjectMapper $objectMapper
    ) {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $slave = $this->getSlave($parameters);
        $portsBySubId = [];

        try {
            $ports = $this->valueRepository->findAttributesByValue(
                $namePart . '*',
                $slave->getTypeId(),
                [IoService::ATTRIBUTE_PORT_KEY_NAME],
                [$slave->getId()],
                true,
                IoService::ATTRIBUTE_TYPE_PORT
            );

            foreach ($ports as $port) {
                $subId = $port->getAttribute()->getSubId();

                if (!isset($portsBySubId[$subId])) {
                    $portsBySubId[$subId] = ['module' => $slave, 'number' => $subId];
                }

                $key = $port->getAttribute()->getKey();

                if (!isset($portsBySubId[$subId][$key])) {
                    $portsBySubId[$subId][$key] = $port->getValue();

                    continue;
                }

                if (is_array($portsBySubId[$subId][$key])) {
                    $portsBySubId[$subId][$key][] = $portsBySubId[$subId][$key];

                    continue;
                }

                $portsBySubId[$subId][$key] = [$portsBySubId[$subId][$key]];
            }

            $ports = array_map(
                fn (array $port): Port => $this->objectMapper->mapToObject(Port::class, $port),
                $portsBySubId
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
    public function getById(string $id, array $parameters): Port
    {
        $slave = $this->getSlave($parameters);

        $values = $this->valueRepository->getByTypeId(
            $slave->getTypeId(),
            (int) $id,
            [$slave->getId() ?? 0],
            IoService::ATTRIBUTE_TYPE_PORT
        );

        return $this->objectMapper->mapToObject(Port::class, $values);
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
        errlog($parameters);

        return $this->moduleRepository->getById((int) $parameters['moduleId']);
    }
}
