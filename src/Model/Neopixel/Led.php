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

/**
 * @method Module getModule()
 * @method Led    setModule(Module $module)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'number'])]
#[Key(columns: ['module_id', 'channel'])]
class Led extends AbstractModel implements \JsonSerializable, AutoCompleteModelInterface
{
    use LedTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $number = 0;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $channel = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Constraint]
    protected Module $module;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Led
    {
        $this->id = $id;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
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

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Led
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'number' => $this->getNumber(),
            'channel' => $this->getChannel(),
            'left' => $this->getLeft(),
            'top' => $this->getTop(),
            'red' => $this->getRed(),
            'green' => $this->getGreen(),
            'blue' => $this->getBlue(),
            'fadeIn' => $this->getFadeIn(),
            'blink' => $this->getBlink(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
