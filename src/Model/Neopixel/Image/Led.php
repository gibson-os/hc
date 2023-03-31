<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Neopixel\Image;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Model\Neopixel\Led as NeopixelLed;
use GibsonOS\Module\Hc\Model\Neopixel\LedTrait;
use JsonSerializable;

/**
 * @method Image       getImage()
 * @method Led         setImage(Image $image)
 * @method NeopixelLed getLed()
 * @method Led         setLed(NeopixelLed $led)
 */
#[Table]
#[Key(unique: true, columns: ['image_id', 'led_id'])]
class Led extends AbstractModel implements JsonSerializable
{
    use LedTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $imageId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $ledId;

    #[Constraint]
    protected Image $image;

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

    public function getImageId(): int
    {
        return $this->imageId;
    }

    public function setImageId(int $imageId): Led
    {
        $this->imageId = $imageId;

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

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'ledId' => $this->getLedId(),
            'red' => $this->getRed(),
            'green' => $this->getGreen(),
            'blue' => $this->getBlue(),
            'fadeIn' => $this->getFadeIn(),
            'blink' => $this->getBlink(),
        ];
    }
}
