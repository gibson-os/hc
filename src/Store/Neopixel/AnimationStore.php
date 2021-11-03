<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Neopixel;

use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Sequence;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\AnimationService as AnimationService;

class AnimationStore extends AbstractDatabaseStore
{
    private ?int $slaveId = null;

    protected function getModelClassName(): string
    {
        return Sequence::class;
    }

    protected function getDefaultOrder(): string
    {
        return '`name`';
    }

    protected function setWheres(): void
    {
        $this->addWhere('`type`=?', [AnimationService::SEQUENCE_TYPE]);

        if ($this->slaveId !== null) {
            $this->addWhere('`module_id`=?', [$this->slaveId]);
        }
    }

    public function setSlaveId(?int $slaveId): AnimationStore
    {
        $this->slaveId = $slaveId;

        return $this;
    }
}
