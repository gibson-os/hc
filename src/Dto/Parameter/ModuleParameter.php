<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Parameter;

use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\AutoComplete\SlaveAutoComplete;
use GibsonOS\Module\Hc\Repository\TypeRepository;

class ModuleParameter extends AutoCompleteParameter
{
    public function __construct(
        SlaveAutoComplete $slaveAutoComplete,
        private readonly TypeRepository $typeRepository,
        string $title = 'Modul',
    ) {
        parent::__construct($title, $slaveAutoComplete);
    }

    /**
     * @throws SelectError
     *
     * @return $this
     */
    public function setTypeHelper(string $helperName): ModuleParameter
    {
        $type = $this->typeRepository->getByHelperName($helperName);
        $this->setParameter('typeId', $type->getId());

        return $this;
    }
}
