<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\AutoComplete\Ir;

use GibsonOS\Core\AutoComplete\AutoCompleteInterface;
use GibsonOS\Core\Exception\AutoCompleteException;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Formatter\IrFormatter;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\IrService;

class KeyAutoComplete implements AutoCompleteInterface
{
    public function __construct(
        private TypeRepository $typeRepository,
        private ValueRepository $valueRepository,
        private IrFormatter $irFormatter
    ) {
    }

    /**
     * @throws SelectError
     *
     * @return AutoCompleteModelInterface[]
     */
    public function getByNamePart(string $namePart, array $parameters): array
    {
        $type = $this->typeRepository->getByHelperName('ir');

        try {
            $keys = $this->valueRepository->findAttributesByValue(
                $namePart . '*',
                $type->getId() ?? 0,
                [IrService::KEY_ATTRIBUTE_NAME],
                subId: true,
                type: IrService::ATTRIBUTE_TYPE_KEY
            );
            $keys = array_map(
                fn (Value $value): Key => $this->irFormatter->getKeyBySubId($value->getAttribute()->getSubId() ?? 0)
                    ->setName($value->getValue()),
                $keys
            );
        } catch (SelectError $e) {
            $keys = [];
        }

        return $keys;
    }

    /**
     * @throws SelectError
     * @throws AutoCompleteException
     */
    public function getById(string $id, array $parameters): AutoCompleteModelInterface
    {
        $type = $this->typeRepository->getByHelperName('ir');

        $values = $this->valueRepository->getByTypeId(
            $type->getId() ?? 0,
            (int) $id,
            type: IrService::ATTRIBUTE_TYPE_KEY
        );
        $value = reset($values);

        if (!$value instanceof Value) {
            throw new AutoCompleteException(sprintf('Key with ID #%d not found', (int) $id));
        }

        return $this->irFormatter->getKeyBySubId($value->getAttribute()->getSubId() ?? 0);
    }

    public function getModel(): string
    {
        return 'GibsonOS.module.hc.ir.model.Key';
    }
}
