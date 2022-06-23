<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Warehouse;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Key;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Code;
use GibsonOS\Module\Hc\Model\Warehouse\Box\File;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Link;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Tag;
use JsonSerializable;

/**
 * @method Module getModule()
 * @method Box    setModule()
 * @method Led[]  getLeds()
 * @method Box    addLeds(Led[] $leds)
 * @method Box    setLeds(Led[] $leds)
 * @method Link[] getLinks()
 * @method Box    addLinks(Link[] $links)
 * @method Box    setLinks(Link[] $links)
 * @method File[] getFiles()
 * @method Box    addFiles(File[] $files)
 * @method Box    setFiles(File[] $files)
 * @method Code[] getCodes()
 * @method Box    addCodes(Code[] $codes)
 * @method Box    setCodes(Code[] $codes)
 * @method Tag[]  getTags()
 * @method Box    addTags(Tag[] $tags)
 * @method Box    setTags(Tag[] $tags)
 */
#[Table]
#[Key(unique: true, columns: ['module_id', 'name'])]
class Box extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $moduleId;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $name;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $image;

    #[Column(type: Column::TYPE_VARCHAR, length: 8)]
    #[Key(true)]
    private string $code;

    #[Column]
    private int $stock = 0;

    #[Column(type: Column::TYPE_VARCHAR, length: 1024)]
    private string $description = '';

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $width = 1;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $height = 1;

    #[Constraint]
    protected Module $module;

    #[Constraint('box', Led::class)]
    protected array $leds = [];

    #[Constraint('box', Link::class)]
    protected array $links = [];

    #[Constraint('box', File::class)]
    protected array $files = [];

    #[Constraint('box', Code::class)]
    protected array $codes = [];

    #[Constraint('box', Tag::class)]
    protected array $tags = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Box
    {
        $this->id = $id;

        return $this;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function setModuleId(int $moduleId): Box
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Box
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): Box
    {
        $this->image = $image;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Box
    {
        $this->code = $code;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): Box
    {
        $this->stock = $stock;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Box
    {
        $this->description = $description;

        return $this;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Box
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Box
    {
        $this->left = $left;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): Box
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): Box
    {
        $this->height = $height;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'image' => $this->getImage(),
            'stock' => $this->getStock(),
            'description' => $this->getDescription(),
            'left' => $this->getLeft(),
            'top' => $this->getTop(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'code' => $this->getCode(),
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
