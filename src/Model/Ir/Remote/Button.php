<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Ir\Remote;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\Event;
use GibsonOS\Module\Hc\Model\Ir\Remote;
use JsonSerializable;

/**
 * @method Remote     getRemote()
 * @method Button     setRemote(Remote $remote)
 * @method Event|null getEvent()
 * @method Button     setEvent(Event|null $event)
 * @method Key[]      getKeys()
 * @method Button     addKeys(Key[] $keys)
 * @method Button     setKeys(Key[] $keys)
 */
#[Table]
class Button extends AbstractModel implements JsonSerializable
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id;

    #[Column(type: Column::TYPE_VARCHAR, length: 64)]
    private string $name;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $width = 1;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $height = 1;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $top = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $left = 0;

    #[Column]
    private bool $borderTop = true;

    #[Column]
    private bool $borderRight = true;

    #[Column]
    private bool $borderBottom = true;

    #[Column]
    private bool $borderLeft = true;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $borderRadiusTopLeft = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $borderRadiusTopRight = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $borderRadiusBottomLeft = 0;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $borderRadiusBottomRight = 0;

    #[Column(type: Column::TYPE_VARCHAR, length: 6)]
    private ?string $background = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $remoteId;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private ?int $eventId;

    #[Constraint]
    protected Remote $remote;

    #[Constraint(onDelete: null)]
    protected ?Event $event;

    #[Constraint('button', Key::class, orderBy: '`order`')]
    protected array $keys = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Button
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Button
    {
        $this->name = $name;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): Button
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): Button
    {
        $this->height = $height;

        return $this;
    }

    public function getTop(): int
    {
        return $this->top;
    }

    public function setTop(int $top): Button
    {
        $this->top = $top;

        return $this;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft(int $left): Button
    {
        $this->left = $left;

        return $this;
    }

    public function hasBorderTop(): bool
    {
        return $this->borderTop;
    }

    public function setBorderTop(bool $borderTop): Button
    {
        $this->borderTop = $borderTop;

        return $this;
    }

    public function hasBorderRight(): bool
    {
        return $this->borderRight;
    }

    public function setBorderRight(bool $borderRight): Button
    {
        $this->borderRight = $borderRight;

        return $this;
    }

    public function hasBorderBottom(): bool
    {
        return $this->borderBottom;
    }

    public function setBorderBottom(bool $borderBottom): Button
    {
        $this->borderBottom = $borderBottom;

        return $this;
    }

    public function hasBorderLeft(): bool
    {
        return $this->borderLeft;
    }

    public function setBorderLeft(bool $borderLeft): Button
    {
        $this->borderLeft = $borderLeft;

        return $this;
    }

    public function getBorderRadiusTopLeft(): int
    {
        return $this->borderRadiusTopLeft;
    }

    public function setBorderRadiusTopLeft(int $borderRadiusTopLeft): Button
    {
        $this->borderRadiusTopLeft = $borderRadiusTopLeft;

        return $this;
    }

    public function getBorderRadiusTopRight(): int
    {
        return $this->borderRadiusTopRight;
    }

    public function setBorderRadiusTopRight(int $borderRadiusTopRight): Button
    {
        $this->borderRadiusTopRight = $borderRadiusTopRight;

        return $this;
    }

    public function getBorderRadiusBottomLeft(): int
    {
        return $this->borderRadiusBottomLeft;
    }

    public function setBorderRadiusBottomLeft(int $borderRadiusBottomLeft): Button
    {
        $this->borderRadiusBottomLeft = $borderRadiusBottomLeft;

        return $this;
    }

    public function getBorderRadiusBottomRight(): int
    {
        return $this->borderRadiusBottomRight;
    }

    public function setBorderRadiusBottomRight(int $borderRadiusBottomRight): Button
    {
        $this->borderRadiusBottomRight = $borderRadiusBottomRight;

        return $this;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(?string $background): Button
    {
        $this->background = $background;

        return $this;
    }

    public function getRemoteId(): int
    {
        return $this->remoteId;
    }

    public function setRemoteId(int $remoteId): Button
    {
        $this->remoteId = $remoteId;

        return $this;
    }

    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    public function setEventId(?int $eventId): Button
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'top' => $this->getTop(),
            'left' => $this->getLeft(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'background' => $this->getBackground(),
            'borderTop' => $this->hasBorderTop(),
            'borderRight' => $this->hasBorderRight(),
            'borderBottom' => $this->hasBorderBottom(),
            'borderLeft' => $this->hasBorderLeft(),
            'borderRadiusTopRight' => $this->getBorderRadiusTopRight(),
            'borderRadiusBottomRight' => $this->getBorderRadiusBottomRight(),
            'borderRadiusBottomLeft' => $this->getBorderRadiusBottomLeft(),
            'borderRadiusTopLeft' => $this->getBorderRadiusTopLeft(),
            'keys' => $this->getKeys(),
            'event' => $this->getEvent(),
        ];
    }
}
