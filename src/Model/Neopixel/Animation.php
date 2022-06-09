<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Neopixel;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led;
use JsonSerializable;

/**
 * @method Module    getModule()
 * @method Animation setModule(Module $module)
 * @method Led[]     getLeds()
 * @method Animation setLeds(Led[] $leds)
 * @method Animation addLeds(Led[] $leds)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'name'])]
#[Key(columns: ['module_id', 'active'])]
#[Key(columns: ['module_id', 'transmitted'])]
class Animation extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Column(length: 64)]
    private ?string $name = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $pid = null;

    #[Column]
    private bool $started = false;

    #[Column]
    private bool $paused = false;

    #[Column]
    private bool $transmitted = false;

    #[Constraint]
    protected Module $module;

    #[Constraint('animation', Led::class, orderBy: '`id`')]
    protected array $leds = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Animation
    {
        $this->id = $id;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Animation
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Animation
    {
        $this->name = $name;

        return $this;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(?int $pid): Animation
    {
        $this->pid = $pid;

        return $this;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function setStarted(bool $started): Animation
    {
        $this->started = $started;

        return $this;
    }

    public function isPaused(): bool
    {
        return $this->paused;
    }

    public function setPaused(bool $paused): Animation
    {
        $this->paused = $paused;

        return $this;
    }

    public function isTransmitted(): bool
    {
        return $this->transmitted;
    }

    public function setTransmitted(bool $transmitted): Animation
    {
        $this->transmitted = $transmitted;

        return $this;
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId() ?? 0,
            'name' => $this->getName(),
            'pid' => $this->getPid(),
            'started' => $this->isStarted(),
            'transmitted' => $this->isTransmitted(),
        ];
    }
}
