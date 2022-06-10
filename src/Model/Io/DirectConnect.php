<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Io;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;
use JsonSerializable;

/**
 * @method Port          getInputPort()
 * @method DirectConnect setInputPort(Port $port)
 * @method Port          getOutputPort()
 * @method DirectConnect setOutputPort(Port $port)
 */
#[Table]
class DirectConnect extends AbstractModel implements JsonSerializable
{
    use PortTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $inputPortId;

    #[Column]
    private bool $inputValue = false;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $outputPortId;

    #[Constraint(name: 'fkHc_io_direct_connectHc_io_input_port', ownColumn: 'input_port_id')]
    protected Port $inputPort;

    #[Constraint(name: 'fkHc_io_direct_connectHc_io_output_port', ownColumn: 'output_port_id')]
    protected Port $outputPort;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): DirectConnect
    {
        $this->id = $id;

        return $this;
    }

    public function getInputPortId(): int
    {
        return $this->inputPortId;
    }

    public function setInputPortId(int $inputPortId): DirectConnect
    {
        $this->inputPortId = $inputPortId;

        return $this;
    }

    public function isInputValue(): bool
    {
        return $this->inputValue;
    }

    public function setInputValue(bool $inputValue): DirectConnect
    {
        $this->inputValue = $inputValue;

        return $this;
    }

    public function getOutputPortId(): int
    {
        return $this->outputPortId;
    }

    public function setOutputPortId(int $outputPortId): DirectConnect
    {
        $this->outputPortId = $outputPortId;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'inputValue' => $this->isInputValue(),
            'value' => $this->isValue(),
            'pwm' => $this->getPwm(),
            'blink' => $this->getBlink(),
            'fadeIn' => $this->getFadeIn(),
            'inputPort' => $this->getInputPort(),
            'outputPort' => $this->getOutputPort(),
        ];
    }
}
