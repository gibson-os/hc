<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Io;

use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Model\Module;
use JsonSerializable;

class Port implements JsonSerializable, AttributeInterface
{
    /**
     * @param string[] $valueNames
     */
    public function __construct(
        private Module $module,
        private int $number,
        #[IsAttribute] private ?string $name = null,
        #[IsAttribute] private Direction $direction = Direction::INPUT,
        #[IsAttribute] private bool $pullUp = true,
        #[IsAttribute] private int $pwm = 0,
        #[IsAttribute] private int $blink = 0,
        #[IsAttribute] private int $delay = 0,
        #[IsAttribute] private bool $value = false,
        #[IsAttribute] private int $fadeIn = 0,
        #[IsAttribute] private array $valueNames = ['Zu', 'Offen'],
    ) {
        $this->setValueNames($this->valueNames);
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getName(): string
    {
        return $this->name ?? 'IO ' . ($this->getNumber() + 1);
    }

    public function setName(string $name): Port
    {
        $this->name = $name;

        return $this;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(Direction $direction): Port
    {
        $this->direction = $direction;

        return $this;
    }

    public function hasPullUp(): bool
    {
        return $this->getDirection() === Direction::OUTPUT ? true : $this->pullUp;
    }

    public function setPullUp(bool $pullUp): Port
    {
        $this->pullUp = $pullUp;

        return $this;
    }

    public function getPwm(): int
    {
        return $this->getDirection() === Direction::INPUT ? 0 : $this->pwm;
    }

    public function setPwm(int $pwm): Port
    {
        $this->pwm = $pwm;

        return $this;
    }

    public function getBlink(): int
    {
        return $this->getDirection() === Direction::INPUT ? 0 : $this->blink;
    }

    public function setBlink(int $blink): Port
    {
        $this->blink = $blink;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->getDirection() === Direction::OUTPUT ? 0 : $this->delay;
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
        return $this->getDirection() === Direction::INPUT ? 0 : $this->fadeIn;
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
        $this->valueNames = array_map('trim', $valueNames);

        return $this;
    }

    public function getSubId(): ?int
    {
        return $this->getNumber();
    }

    public function getModule(): Module
    {
        return $this->module;
    }

    public function jsonSerialize(): array
    {
        return [
            'number' => $this->getNumber(),
            'name' => $this->getName(),
            'direction' => $this->getDirection()->value,
            'pullUp' => $this->hasPullUp(),
            'pwm' => $this->getPwm(),
            'blink' => $this->getBlink(),
            'delay' => $this->getDelay(),
            'value' => $this->isValue(),
            'fadeIn' => $this->getFadeIn(),
            'valueNames' => $this->getValueNames(),
        ];
    }
}
