<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Neopixel;

use GibsonOS\Module\Hc\Attribute\IsAttribute;
use GibsonOS\Module\Hc\Dto\AttributeInterface;
use GibsonOS\Module\Hc\Model\Module;
use JsonSerializable;

class Led implements JsonSerializable, AttributeInterface
{
    private int $length = 0;

    private int $time = 0;

    private bool $onlyColor = false;

    private bool $forAnimation = false;

    public function __construct(
        private Module $module,
        private int $number = 0,
        #[IsAttribute] private int $channel = 0,
        #[IsAttribute] private int $top = 0,
        #[IsAttribute] private int $left = 0,
        #[IsAttribute] private int $red = 0,
        #[IsAttribute] private int $green = 0,
        #[IsAttribute] private int $blue = 0,
        #[IsAttribute] private int $fadeIn = 0,
        #[IsAttribute] private int $blink = 0,
    ) {
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Led
    {
        $this->number = $number;

        return $this;
    }

    public function getChannel(): int
    {
        return $this->channel;
    }

    public function setChannel(int $channel): Led
    {
        $this->channel = $channel;

        return $this;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Led
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Led
    {
        $this->left = $left;

        return $this;
    }

    public function getRed(): int
    {
        return $this->red;
    }

    public function setRed(int $red): Led
    {
        $this->red = $red;

        return $this;
    }

    public function getGreen(): int
    {
        return $this->green;
    }

    public function setGreen(int $green): Led
    {
        $this->green = $green;

        return $this;
    }

    public function getBlue(): int
    {
        return $this->blue;
    }

    public function setBlue(int $blue): Led
    {
        $this->blue = $blue;

        return $this;
    }

    public function getFadeIn(): int
    {
        return $this->fadeIn;
    }

    public function setFadeIn(int $fadeIn): Led
    {
        $this->fadeIn = $fadeIn;

        return $this;
    }

    public function getBlink(): int
    {
        return $this->blink;
    }

    public function setBlink(int $blink): Led
    {
        $this->blink = $blink;

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

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): Led
    {
        $this->time = $time;

        return $this;
    }

    public function isOnlyColor(): bool
    {
        return $this->onlyColor;
    }

    public function setOnlyColor(bool $onlyColor): Led
    {
        $this->onlyColor = $onlyColor;

        return $this;
    }

    public function isForAnimation(): bool
    {
        return $this->forAnimation;
    }

    public function setForAnimation(bool $forAnimation): Led
    {
        $this->forAnimation = $forAnimation;

        return $this;
    }

    public function jsonSerialize(): array
    {
        $json = [
            'number' => $this->getNumber(),
            'red' => $this->getRed(),
            'green' => $this->getGreen(),
            'blue' => $this->getBlue(),
            'fadeIn' => $this->getFadeIn(),
            'blink' => $this->getBlink(),
        ];

        if (!$this->isOnlyColor()) {
            $json['channel'] = $this->getChannel();
            $json['left'] = $this->getLeft();
            $json['top'] = $this->getTop();
        }

        if ($this->isForAnimation()) {
            $json['length'] = $this->getLength();
            $json['time'] = $this->getTime();
        }

        return $json;
    }

    public function getSubId(): ?int
    {
        return $this->number;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }
}
