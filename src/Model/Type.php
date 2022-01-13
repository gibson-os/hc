<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use JsonSerializable;

#[Table]
class Type extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(length: 32)]
    private string $name;

    #[Column(length: 32)]
    private string $helper;

    #[Column]
    private bool $network = false;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $hertz;

    #[Column]
    private bool $isHcSlave = false;

    #[Column]
    private bool $hasInput = false;

    #[Column(type: Column::TYPE_TEXT)]
    private ?string $uiSettings = null;

    public static function getTableName(): string
    {
        return 'hc_type';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Type
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Type
    {
        $this->name = $name;

        return $this;
    }

    public function getHelper(): string
    {
        return $this->helper;
    }

    public function setHelper(string $helper): Type
    {
        $this->helper = $helper;

        return $this;
    }

    public function getNetwork(): bool
    {
        return $this->network;
    }

    public function isNetwork(bool $network): Type
    {
        $this->network = $network;

        return $this;
    }

    public function getHertz(): int
    {
        return $this->hertz;
    }

    public function setHertz(int $hertz): Type
    {
        $this->hertz = $hertz;

        return $this;
    }

    public function getIsHcSlave(): bool
    {
        return $this->isHcSlave;
    }

    public function setIsHcSlave(bool $isHcSlave): Type
    {
        $this->isHcSlave = $isHcSlave;

        return $this;
    }

    public function getHasInput(): bool
    {
        return $this->hasInput;
    }

    public function setHasInput(bool $hasInput): Type
    {
        $this->hasInput = $hasInput;

        return $this;
    }

    public function getUiSettings(): ?string
    {
        return $this->uiSettings;
    }

    public function setUiSettings(?string $uiSettings): Type
    {
        $this->uiSettings = $uiSettings;

        return $this;
    }

    public function getAutoCompleteId(): int
    {
        return (int) $this->getId();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'helper' => $this->getHelper(),
            'hertz' => $this->getHertz(),
            'network' => $this->getNetwork(),
            'uiSettings' => $this->getUiSettings(),
            'hasInput' => $this->getHasInput(),
            'isHcSlave' => $this->getIsHcSlave(),
        ];
    }
}
