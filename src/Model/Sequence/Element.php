<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Sequence;

use DateTimeInterface;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Sequence;

class Element extends AbstractModel
{
    private ?int $id;

    private int $sequenceId;

    private string $data;

    private int $order = 0;

    private ?DateTimeInterface $added;

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

    public function getAdded(): ?DateTimeInterface
    {
        return $this->added;
    }

    public function setAdded(?DateTimeInterface $added): Element
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @throws DateTimeError
     */
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
