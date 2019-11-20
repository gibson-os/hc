<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Sequence;

use DateTime;
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

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'hc_sequence_element';
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     *
     * @return Element
     */
    public function setId(?int $id): Element
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getSequenceId(): int
    {
        return $this->sequenceId;
    }

    /**
     * @param int $sequenceId
     *
     * @return Element
     */
    public function setSequenceId(int $sequenceId): Element
    {
        $this->sequenceId = $sequenceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return Element
     */
    public function setData(string $data): Element
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     *
     * @return Element
     */
    public function setOrder(int $order): Element
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAdded(): ?DateTime
    {
        return $this->added;
    }

    /**
     * @param DateTime|null $added
     *
     * @return Element
     */
    public function setAdded(?DateTime $added): Element
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return Sequence
     */
    public function getSequence(): Sequence
    {
        return $this->sequence;
    }

    /**
     * @param Sequence $sequence
     *
     * @return Element
     */
    public function setSequence(Sequence $sequence): Element
    {
        $this->sequence = $sequence;
        $this->setSequenceId($sequence->getId());

        return $this;
    }

    /**
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
