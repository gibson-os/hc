<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use DateTimeInterface;
use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;
use JsonSerializable;

#[Table]
class Master extends AbstractModel implements JsonSerializable, AutoCompleteModelInterface
{
    public const PROTOCOL_UDP = 'udp';

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(length: 64)]
    private string $name;

    #[Column(type: Column::TYPE_ENUM, values: ['udp', 'rfm'])]
    private string $protocol;

    #[Column(length: 39)]
    private string $address;

    #[Column(type: Column::TYPE_SMALLINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $sendPort;

    #[Column(default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private ?DateTimeInterface $added = null;

    #[Column(attributes: [Column::ATTRIBUTE_CURRENT_TIMESTAMP])]
    private ?DateTimeInterface $modified = null;

    public static function getTableName(): string
    {
        return 'hc_master';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Master
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Master
    {
        $this->name = $name;

        return $this;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function setProtocol(string $protocol): Master
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): Master
    {
        $this->address = $address;

        return $this;
    }

    public function getSendPort(): int
    {
        return $this->sendPort;
    }

    public function setSendPort(int $sendPort): Master
    {
        $this->sendPort = $sendPort;

        return $this;
    }

    public function getAdded(): ?DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(DateTimeInterface $added): Master
    {
        $this->added = $added;

        return $this;
    }

    public function getModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(DateTimeInterface $modified): Master
    {
        $this->modified = $modified;

        return $this;
    }

    public function getAutoCompleteId(): int
    {
        return (int) $this->getId();
    }

    public function jsonSerialize(): array
    {
        $added = $this->getAdded();
        $modified = $this->getModified();

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'protocol' => $this->getProtocol(),
            'address' => $this->getAddress(),
            'added' => $added?->format('Y-m-d H:i:s'),
            'modified' => $modified?->format('Y-m-d H:i:s'),
        ];
    }
}
