<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Neopixel;

use JsonSerializable;

class Led implements JsonSerializable
{
    private int $number;

    private int $channel;

    private int $top = 0;

    private int $left = 0;

    private int $red = 0;

    private int $green = 0;

    private int $blue = 0;

    private int $fadeIn = 0;

    private int $blink = 0;

    public function __construct(int $number, int $channel)
    {
        $this->number = $number;
        $this->channel = $channel;
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

    public function jsonSerialize(): array
    {
        return [
            'number' => $this->getNumber(),
            'channel' => $this->getChannel(),
            'top' => $this->getTop(),
            'left' => $this->getLeft(),
            'red' => $this->getRed(),
            'green' => $this->getGreen(),
            'blue' => $this->getBlue(),
            'fadeIn' => $this->getFadeIn(),
            'blink' => $this->getBlink(),
        ];
    }
}
