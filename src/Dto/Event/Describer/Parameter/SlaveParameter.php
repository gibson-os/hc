<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

use GibsonOS\Core\Dto\Event\Describer\Parameter\AutoCompleteParameter;
use GibsonOS\Module\Hc\Model\Type;

class SlaveParameter extends AutoCompleteParameter
{
    /**
     * SlaveParameter constructor.
     */
    public function __construct(string $title = 'Sklave')
    {
        parent::__construct($title, 'hc/slave/autoComplete', 'GibsonOS.module.hc.event.model.Slave');
    }

    public function setSlaveType(Type $slaveType): SlaveParameter
    {
        $this->setParameter('typeId', $slaveType->getId());

        return $this;
    }
}
