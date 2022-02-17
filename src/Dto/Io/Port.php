<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Model\Module;

class Port implements AttributeInterface
{
    public const DIRECTION_INPUT = 0;

    public const DIRECTION_OUTPUT = 1;

    /**
     * @param string[] $valueNames
     */
    public function __construct(
        private Module $module,
        private int $number,
        #[IsAttribute] private ?string $name = null,
        #[IsAttribute] private int $direction = self::DIRECTION_INPUT,
        #[IsAttribute] private bool $pullUp = true,
        #[IsAttribute] private int $pwm = 0,
        #[IsAttribute] private int $blink = 0,
        #[IsAttribute] private int $delay = 0,
        #[IsAttribute] private bool $value = false,
        #[IsAttribute] private int $fadeIn = 0,
        #[IsAttribute] private array $valueNames = ['Zu', 'Offen'],
    ) {
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getName(): string
    {
        return $this->name ?? 'IO ' . $this->getNumber();
    }

    public function getDirection(): int
    {
        return $this->direction;
    }

    public function setDirection(int $direction): Port
    {
        $this->direction = $direction;

        return $this;
    }

    public function isPullUp(): bool
    {
        return $this->pullUp;
    }

    public function setPullUp(bool $pullUp): Port
    {
        $this->pullUp = $pullUp;

        return $this;
    }

    public function getPwm(): int
    {
        return $this->pwm;
    }

    public function setPwm(int $pwm): Port
    {
        $this->pwm = $pwm;

        return $this;
    }

    public function getBlink(): int
    {
        return $this->blink;
    }

    public function setBlink(int $blink): Port
    {
        $this->blink = $blink;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): Port
    {
        $this->delay = $delay;

        return $this;
    }

    public function isValue(): bool
    {
        return $this->value;
    }

    public function setValue(bool $value): Port
    {
        $this->value = $value;

        return $this;
    }

    public function getFadeIn(): int
    {
        return $this->fadeIn;
    }

    public function setFadeIn(int $fadeIn): Port
    {
        $this->fadeIn = $fadeIn;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getValueNames(): array
    {
        return $this->valueNames;
    }

    /**
     * @param array|string[] $valueNames
     */
    public function setValueNames(array $valueNames): Port
    {
        $this->valueNames = $valueNames;

        return $this;
    }

    public function getSubId(): ?int
    {
        return $this->getNumber();
    }

    public function getTypeName(): string
    {
        return 'io';
    }

    public function getModule(): Module
    {
        return $this->module;
    }
}