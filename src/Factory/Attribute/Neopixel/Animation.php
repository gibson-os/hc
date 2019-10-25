<?php
namespace GibsonOS\Module\Hc\Factory\Attribute\Neopixel;

use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;
use GibsonOS\Module\Hc\Service\Attribute\Neopixel\Animation as AnimationAttribute;

class Animation
{
    /**
     * @param Module $slave
     * @return AnimationAttribute
     */
    public static function create(Module $slave): AnimationAttribute
    {
        return new AnimationAttribute($slave, new AttributeRepository(), new ValueRepository());
    }
}