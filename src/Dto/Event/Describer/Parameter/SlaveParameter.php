<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

use GibsonOS\Core\Dto\Event\Describer\Parameter\AutoCompleteParameter;
use GibsonOS\Module\Hc\Event\AutoComplete\SlaveAutoComplete;
use GibsonOS\Module\Hc\Model\Type;

class SlaveParameter extends AutoCompleteParameter
{
    /**
     * @var SlaveAutoComplete
     */
    private $slaveAutoComplete;

    /**
     * SlaveParameter constructor.
     */
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
