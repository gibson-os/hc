<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Attribute;

use GibsonOS\Core\Attribute\AttributeInterface;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Mapper\ObjectMapper;
use GibsonOS\Core\Service\Attribute\ObjectMapperAttribute;
use GibsonOS\Core\Service\RequestService;
use GibsonOS\Module\Hc\Attribute\GetAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface as AttributeDtoInterface;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use JsonException;
use ReflectionException;
use ReflectionParameter;

class AttributeMapperAttribute extends ObjectMapperAttribute
{
    public function __construct(
        ObjectMapper $objectMapper,
        RequestService $requestService,
        ReflectionManager $reflectionManager,
        private AttributeRepository $attributeRepository
    ) {
        parent::__construct($objectMapper, $requestService, $reflectionManager);
    }

    /**
     * @throws MapperException
     * @throws FactoryError
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function replace(AttributeInterface $attribute, array $parameters, ReflectionParameter $reflectionParameter): ?AttributeDtoInterface
    {
        if (!$attribute instanceof GetAttribute) {
            return null;
        }

        $dto = parent::replace($attribute, $parameters, $reflectionParameter);

        if (!$dto instanceof AttributeDtoInterface) {
            throw new MapperException(sprintf(
                'Object "%s" is no instance of "%s"!',
                $dto::class,
                AttributeDtoInterface::class
            ));
        }

        if ($dto->getSubId() !== null) {
            $this->attributeRepository->loadDto($dto);
        }

        return $dto;
    }
}
