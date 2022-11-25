<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Ir;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key as KeyAttribute;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Dto\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key\Name;

/**
 * @method Name[] getNames()
 * @method Key    unloadNames()
 * @method Key    addNames(Name[] $names)
 * @method Key    setNames(Name[] $names)
 */
#[Table]
#[KeyAttribute(unique: true, columns: ['protocol', 'address', 'command'])]
class Key extends AbstractModel implements \JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column]
    private Protocol $protocol;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $address;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $command;

    #[Constraint('key', Name::class, orderBy: '`name`')]
    protected array $names = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Key
    {
        $this->id = $id;

        return $this;
    }

    public function getProtocol(): Protocol
    {
        return $this->protocol;
    }

    public function setProtocol(Protocol $protocol): Key
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function setAddress(int $address): Key
    {
        $this->address = $address;

        return $this;
    }

    public function getCommand(): int
    {
        return $this->command;
    }

    public function setCommand(int $command): Key
    {
        $this->command = $command;

        return $this;
    }

    public function jsonSerialize(): array
    {
        $names = $this->getNames();

        return [
            'id' => $this->getId(),
            'name' => implode(', ', array_map(static fn (Name $name): string => $name->getName(), $names)),
            'names' => $this->getNames(),
            'protocol' => $this->getProtocol()->value,
            'command' => $this->getCommand(),
            'address' => $this->getAddress(),
            'protocolName' => $this->getProtocol()->getName(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
