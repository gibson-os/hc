<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory\Sequence\Neopixel;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;
use GibsonOS\Module\Hc\Service\Sequence\Neopixel\ImageService as ImageService;

class Image
{
    /**
     * @param int $slaveId
     *
     * @throws SelectError
     *
     * @return ImageService
     */
    public static function createBySlaveId(int $slaveId): ImageService
    {
        return self::create(ModuleRepository::getById($slaveId));
    }

    /**
     * @param Module $slave
     *
     * @return ImageService
     */
    public static function create(Module $slave): ImageService
    {
        return new ImageService($slave);
    }
}
