<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Neopixel;

use GibsonOS\Core\Attribute\Install\Database\Column;

trait LedTrait
{
    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $red = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $green = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $blue = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $fadeIn = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $blink = 0;

    public function getRed(): int
    {
        return $this->red;
    }

    public function setRed(int $red): self
    {
        $this->red = $red;

        return $this;
    }

    public function getGreen(): int
    {
        return $this->green;
    }

    public function setGreen(int $green): self
    {
        $this->green = $green;

        return $this;
    }

    public function getBlue(): int
    {
        return $this->blue;
    }

    public function setBlue(int $blue): self
    {
        $this->blue = $blue;

        return $this;
    }

    public function getFadeIn(): int
    {
        return $this->fadeIn;
    }

    public function setFadeIn(int $fadeIn): self
    {
        $this->fadeIn = $fadeIn;

        return $this;
    }

    public function getBlink(): int
    {
        return $this->blink;
    }

    public function setBlink(int $blink): self
    {
        $this->blink = $blink;

        return $this;
    }
}
