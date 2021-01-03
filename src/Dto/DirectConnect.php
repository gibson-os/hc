<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto;

use GibsonOS\Module\Hc\Dto\DirectConnect\Step;
use GibsonOS\Module\Hc\Dto\DirectConnect\Trigger;

class DirectConnect
{
    private int $id;

    /**
     * @var Step[]
     */
    private array $steps = [];

    /**
     * @var Trigger[]
     */
    private array $triggers = [];

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): DirectConnect
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Step[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param Step[] $steps
     */
    public function setSteps(array $steps): DirectConnect
    {
        $this->steps = $steps;

        return $this;
    }

    /**
     * @return Trigger[]
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * @param Trigger[] $triggers
     */
    public function setTriggers(array $triggers): DirectConnect
    {
        $this->triggers = $triggers;

        return $this;
    }
}
