<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Neopixel\Animation;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Neopixel\Led as NeopixelLed;
use GibsonOS\Module\Hc\Model\Neopixel\LedTrait;

/**
 * @method Step        getStep()
 * @method Led         setStep(Step $step)
 * @method NeopixelLed getLed()
 * @method Led         setLed(NeopixelLed $led)
 */
#[Table]
#[Key(unique: true, columns: ['animation_id', 'led_id'])]
class Led extends AbstractModel
{
    use LedTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $stepId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $ledId;

    #[Constraint]
    protected Step $step;

    #[Constraint]
    protected NeopixelLed $led;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Led
    {
        $this->id = $id;

        return $this;
    }

    public function getStepId(): int
    {
        return $this->stepId;
    }

    public function setStepId(int $stepId): Led
    {
        $this->stepId = $stepId;

        return $this;
    }

    public function getLedId(): int
    {
        return $this->ledId;
    }

    public function setLedId(int $ledId): Led
    {
        $this->ledId = $ledId;

        return $this;
    }
}
