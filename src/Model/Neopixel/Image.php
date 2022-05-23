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
use GibsonOS\Module\Hc\Model\Neopixel\Image\Led;

/**
 * @method Module getModule()
 * @method Image  setModule(Module $module)
 * @method Led[]  getLeds()
 * @method Image  setLeds(Led[] $leds)
 * @method Image  addLeds(Led[] $leds)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'name'])]
class Image extends AbstractModel implements AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Column(length: 64)]
    private string $name;

    #[Constraint]
    protected Module $module;

    #[Constraint('image', Led::class)]
    protected array $leds = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Image
    {
        $this->id = $id;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Image
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Image
    {
        $this->name = $name;

        return $this;
    }

    public function getAutoCompleteId(): string|int|float
    {
        return $this->getId() ?? 0;
    }
}
