<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Sequence;

use DateTime;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Module\Hc\Model\Sequence;

class Element extends AbstractModel
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int
     */
    private $sequenceId;

    /**
     * @var string
     */
    private $data;

    /**
     * @var int
     */
    private $order = 0;

    /**
     * @var DateTime|null
     */
    private $added;

    /**
     * @var Sequence
     */
    private $sequence;

    public static function getTableName(): string
    {
        return 'hc_sequence_element';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Element
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

    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    public function setAdded(?DateTime $added): Element
    {
        $this->added = $added;

        return $this;
    }

    public function getSequence(): Sequence
    {
        return $this->sequence;
    }

    public function setSequence(Sequence $sequence): Element
    {
        $this->sequence = $sequence;
        $this->setSequenceId((int) $sequence->getId());

        return $this;
    }

    /**
     * @throws DateTimeError
     * @throws SelectError
     *
     * @return Element
     */
    public function loadSequence()
    {
        $this->loadForeignRecord($this->getSequence(), $this->getSequenceId());

        return $this;
    }
}
