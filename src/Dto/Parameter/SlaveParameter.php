<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Parameter;

use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Module\Hc\AutoComplete\SlaveAutoComplete;
use GibsonOS\Module\Hc\Model\Type;

class SlaveParameter extends AutoCompleteParameter
{
    private SlaveAutoComplete $slaveAutoComplete;

    public function __construct(SlaveAutoComplete $slaveAutoComplete, string $title = 'Sklave')
    {
        parent::__construct($title, $slaveAutoComplete);
        $this->slaveAutoComplete = $slaveAutoComplete;
    }

    public function setSlaveType(Type $slaveType): SlaveParameter
    {
        $this->setParameter('typeId', $slaveType->getId());

        return $this;
    }
}
