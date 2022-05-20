<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Neopixel;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Led;
use GibsonOS\Module\Hc\Model\Neopixel\Animation\Step;

/**
 * @method Module    getModule()
 * @method Animation setModule(Module $module)
 * @method Step[]    getSteps()
 * @method Animation setSteps(Step[] $steps)
 * @method Animation addSteps(Step[] $steps)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'name'])]
class Animation extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Column(length: 64)]
    private string $name;

    #[Constraint]
    protected Module $module;

    #[Constraint('animation', Led::class)]
    protected array $steps = [];

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Animation
    {
        $this->name = $name;

        return $this;
    }

    public function getAutoCompleteId(): string|int|float
    {
        return $this->getId() ?? 0;
    }
}
