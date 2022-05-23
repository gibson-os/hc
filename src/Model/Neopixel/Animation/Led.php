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
#[Key(unique: true, columns: ['step_id', 'number'])]
class Led extends AbstractModel
{
    use LedTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $stepId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $length = 0;

    #[Constraint]
    protected Step $step;

    #[Constraint(onDelete: null, ownColumn: 'number')]
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

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): Led
    {
        $this->length = $length;

        return $this;
    }
}
