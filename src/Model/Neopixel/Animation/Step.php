<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Neopixel\Animation;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use JsonSerializable;

/**
 * @method Animation getAnimation()
 * @method Step      setAnimation(Animation $animation)
 * @method Led[]     getLeds()
 * @method Step      setLeds(Led[] $leds)
 * @method Step      addLeds(Led[] $leds)
 */
#[Table]
class Step extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $time = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $animationId;

    #[Constraint]
    protected Animation $animation;

    #[Constraint('step', Led::class)]
    protected array $leds;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Step
    {
        $this->id = $id;

        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): Step
    {
        $this->time = $time;

        return $this;
    }

    public function getAnimationId(): int
    {
        return $this->animationId;
    }

    public function setAnimationId(int $animationId): Step
    {
        $this->animationId = $animationId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'time' => $this->getTime(),
            'leds' => $this->getLeds(),
        ];
    }
}
