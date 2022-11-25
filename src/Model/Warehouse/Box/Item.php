<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse\Box;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item\Code;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item\File;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item\Link;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item\Tag;

/**
 * @method Box    getBox()
 * @method Item   setBox(Box $box)
 * @method Link[] getLinks()
 * @method Item   addLinks(Link[] $links)
 * @method Item   setLinks(Link[] $links)
 * @method File[] getFiles()
 * @method Item   addFiles(File[] $files)
 * @method Item   setFiles(File[] $files)
 * @method Code[] getCodes()
 * @method Item   addCodes(Code[] $codes)
 * @method Item   setCodes(Code[] $codes)
 * @method Tag[]  getTags()
 * @method Item   addTags(Tag[] $tags)
 * @method Item   setTags(Tag[] $tags)
 */
#[Table]
#[Key(unique: true, columns: ['box_id', 'name'])]
class Item extends AbstractModel implements \JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $boxId;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $name;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private ?string $image = null;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private ?string $imageMimeType = null;

    #[Column]
    private int $stock = 0;

    #[Column(type: Column::TYPE_VARCHAR, length: 1024)]
    private string $description = '';

    #[Constraint]
    protected Box $box;

    #[Constraint('item', Link::class)]
    protected array $links = [];

    #[Constraint('item', File::class)]
    protected array $files = [];

    #[Constraint('item', Code::class)]
    protected array $codes = [];

    #[Constraint('item', Tag::class)]
    protected array $tags = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Item
    {
        $this->id = $id;

        return $this;
    }

    public function getBoxId(): int
    {
        return $this->boxId;
    }

    public function setBoxId(int $boxId): Item
    {
        $this->boxId = $boxId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Item
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): Item
    {
        $this->image = $image;

        return $this;
    }

    public function getImageMimeType(): ?string
    {
        return $this->imageMimeType;
    }

    public function setImageMimeType(?string $imageMimeType): Item
    {
        $this->imageMimeType = $imageMimeType;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): Item
    {
        $this->stock = $stock;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Item
    {
        $this->description = $description;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'image' => $this->getImage(),
            'imageMimeType' => $this->getImageMimeType(),
            'stock' => $this->getStock(),
            'description' => $this->getDescription(),
            'codes' => $this->getCodes(),
            'links' => $this->getLinks(),
            'files' => $this->getFiles(),
            'tags' => $this->getTags(),
        ];
    }

    public function getAutoCompleteId(): int
    {
        return $this->getId() ?? 0;
    }
}
