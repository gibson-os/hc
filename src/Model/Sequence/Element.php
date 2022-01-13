<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Sequence;

use DateTimeInterface;
use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Sequence;

#[Table]
class Element extends AbstractModel
{
    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $sequenceId;

    #[Column(type: Column::TYPE_TEXT)]
    private string $data;

    #[Column(type: Column::TYPE_TINYINT, attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $order = 0;

    #[Column(type: Column::TYPE_TIMESTAMP, default: Column::DEFAULT_CURRENT_TIMESTAMP)]
    private DateTimeInterface $added;

    private Sequence $sequence;

    public static function getTableName(): string
    {
        return 'hc_sequence_element';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Element
    {
        $this->id = $id;

        return $this;
    }

    public function getSequenceId(): int
    {
        return $this->sequenceId;
    }

    public function setSequenceId(int $sequenceId): Element
    {
        $this->sequenceId = $sequenceId;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): Element
    {
        $this->data = $data;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): Element
    {
        $this->order = $order;

        return $this;
    }

    public function getAdded(): DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(DateTimeInterface $added): Element
    {
        $this->added = $added;

        return $this;
    }

    public function getSequence(): Sequence
    {
        $this->loadForeignRecord($this->sequence, $this->getSequenceId());

        return $this->sequence;
    }

    public function setSequence(Sequence $sequence): Element
    {
        $this->sequence = $sequence;
        $this->setSequenceId((int) $sequence->getId());

        return $this;
    }
}
