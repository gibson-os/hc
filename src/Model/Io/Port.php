<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Io;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Dto\Io\Direction;
use GibsonOS\Module\Hc\Model\Module;
use JsonSerializable;

/**
 * @method Module          getModule()
 * @method Port            setModule(Module $module)
 * @method DirectConnect[] getDirectConnects()
 * @method Port            setDirectConnects(DirectConnect[] $directConnects)
 * @method Port            addDirectConnects(DirectConnect[] $directConnects)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'number'])]
class Port extends AbstractModel implements AutoCompleteModelInterface, JsonSerializable
{
    use PortTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column]
    private Direction $direction = Direction::INPUT;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $number;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $name;

    #[Column]
    private bool $pullUp = true;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $delay = 0;

    /**
     * @var string[]
     */
    #[Column]
    private array $valueNames = ['Offen', 'Zu'];

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Constraint]
    protected Module $module;

    #[Constraint('inputPort', DirectConnect::class)]
    protected array $directConnects;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Port
    {
        $this->id = $id;

        return $this;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(Direction $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Port
    {
        $this->number = $number;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Port
    {
        $this->name = $name;

        return $this;
    }

    public function hasPullUp(): bool
    {
        return $this->pullUp;
    }

    public function setPullUp(bool $pullUp): self
    {
        $this->pullUp = $pullUp;

        return $this;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getValueNames(): array
    {
        return $this->valueNames;
    }

    /**
     * @param string[] $valueNames
     */
    public function setValueNames(array $valueNames): Port
    {
        $this->valueNames = $valueNames;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Port
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'number' => $this->getNumber(),
            'direction' => $this->getDirection()->value,
            'pullUp' => $this->hasPullUp(),
            'delay' => $this->getDelay(),
            'value' => $this->isValue(),
            'valueNames' => $this->getValueNames(),
            'pwm' => $this->getPwm(),
            'blink' => $this->getBlink(),
            'fadeIn' => $this->getFadeIn(),
        ];
    }
}
