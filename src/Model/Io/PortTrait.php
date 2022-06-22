<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Io;

use GibsonOS\Core\Attribute\Install\Database\Column;

trait PortTrait
{
    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $pwm = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $blink = 0;

    #[Column]
    private bool $value = false;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $fadeIn = 0;

    public function getPwm(): int
    {
        return $this->pwm;
    }

    public function setPwm(int $pwm): self
    {
        $this->pwm = $pwm;

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

    public function isValue(): bool
    {
        return $this->value;
    }

    public function setValue(bool $value): self
    {
        $this->value = $value;

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
}
