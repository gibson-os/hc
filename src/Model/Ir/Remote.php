<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Ir;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key as KeyAttribute;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Ir\Remote\Button;
use JsonSerializable;

/**
 * @method Button[] getButtons()
 * @method Remote   addButtons(Button[] $buttons)
 * @method Remote   setButtons(Button[] $buttons)
 */
#[Table]
class Remote extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    #[KeyAttribute(true)]
    private string $name;

    #[Constraint('remote', Button::class)]
    protected array $buttons = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Remote
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Remote
    {
        $this->name = $name;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }
}
