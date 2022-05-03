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

// @todo Alles irgendwie scheiße! Mapping ist doof. getModule ist doof. mal per id mal direkt drin. Alles doof!
class PortAutoComplete implements AutoCompleteInterface
{
    public function __construct(
        private readonly ModuleRepository $moduleRepository,
        private readonly ValueRepository $valueRepository,
        private readonly ObjectMapper $objectMapper
    ) {
    }

    /**
     * @throws SelectError
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $module = $this->getModule($parameters);

        try {
            $ports = $this->valueRepository->findAttributesByValue(
                $namePart . '*',
                $module->getTypeId(),
                [IoService::ATTRIBUTE_PORT_KEY_NAME],
                [$module->getId()],
                true,
                IoService::ATTRIBUTE_TYPE_PORT
            );

            $ports = array_map(
                fn (array $port): Port => $this->objectMapper->mapToObject(Port::class, $port),
                $this->getPortsBySubId($ports, $module)
            );
        } catch (SelectError) {
            $ports = [];
        }

        return $ports;
    }

    /**
     * @throws SelectError
     */
    public function getById(string $id, array $parameters): Port
    {
        $module = $this->getModule($parameters);

        $values = $this->valueRepository->getByTypeId(
            $module->getTypeId(),
            (int) $id,
            [$module->getId() ?? 0],
            IoService::ATTRIBUTE_TYPE_PORT
        );

        $ports = $this->getPortsBySubId($values, $module);

        return $this->objectMapper->mapToObject(Port::class, reset($ports));
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.io.model.Port';
    }

    /**
     * @throws SelectError
     */
    private function getModule(array $parameters): Module
    {
        if ($parameters['module'] instanceof Module) {
            return $parameters['module'];
        }

        return $this->moduleRepository->getById((int) $parameters['moduleId']);
    }

    private function getPortsBySubId(array $ports, Module $module): array
    {
        $portsBySubId = [];

        foreach ($ports as $port) {
            $subId = $port->getAttribute()->getSubId();

            if (!isset($portsBySubId[$subId])) {
                $portsBySubId[$subId] = ['module' => $module, 'number' => $subId];
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

        return $portsBySubId;
    }
}
