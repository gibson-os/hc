<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Element;
use GibsonOS\Module\Hc\Model\Warehouse\Label\Template;
use JsonSerializable;

/**
 * @method Template  getTemplate()
 * @method Label     setTemplate(Template $template)
 * @method Element[] getElements()
 * @method Label     setElements(Element[] $elements)
 * @method Label     addElements(Element[] $elements)
 */
#[Table]
class Label extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 128)]
    #[Key(true)]
    private string $name;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $templateId;

    #[Constraint]
    protected Template $template;

    #[Constraint('label', Element::class)]
    protected array $elements = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Label
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Label
    {
        $this->name = $name;

        return $this;
    }

    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    public function setTemplateId(int $templateId): Label
    {
        $this->templateId = $templateId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
