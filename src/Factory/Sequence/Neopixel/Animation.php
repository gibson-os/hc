<?php
namespace GibsonOS\Module\Hc\Factory\Sequence\Neopixel;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\Animation as AnimationService;

class Animation
{
    /**
     * @param int $slaveId
     * @return AnimationService
     * @throws SelectError
     */
    public static function createBySlaveId(int $slaveId): AnimationService
    {
        return self::create(ModuleRepository::getById($slaveId));
    }

    /**
     * @param Module $slave
     * @return AnimationService
     */
    public static function create(Module $slave): AnimationService
    {
        return new AnimationService($slave);
    }
}