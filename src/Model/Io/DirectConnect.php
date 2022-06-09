<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model\Io;

use GibsonOS\Core\Attribute\Install\Database\Column;
use GibsonOS\Core\Attribute\Install\Database\Constraint;
use GibsonOS\Core\Attribute\Install\Database\Table;
use GibsonOS\Core\Model\AbstractModel;

/**
 * @method Port          getInput()
 * @method DirectConnect setInput()
 * @method Port          getOutput()
 * @method DirectConnect setOutput()
 */
#[Table]
class DirectConnect extends AbstractModel
{
    use PortTrait;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED], autoIncrement: true)]
    private ?int $id = null;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $inputId;

    #[Column]
    private bool $inputValue = false;

    #[Column(attributes: [Column::ATTRIBUTE_UNSIGNED])]
    private int $outputId;

    #[Constraint(name: 'fkHc_io_direct_connectHc_io_input_port', ownColumn: 'input_id')]
    protected Port $input;

    #[Constraint(name: 'fkHc_io_direct_connectHc_io_output_port', ownColumn: 'output_id')]
    protected Port $output;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): DirectConnect
    {
        $this->id = $id;

        return $this;
    }

    public function getInputId(): int
    {
        return $this->inputId;
    }

    public function setInputId(int $inputId): DirectConnect
    {
        $this->inputId = $inputId;

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

    public function getOutputId(): int
    {
        return $this->outputId;
    }

    public function setOutputId(int $outputId): DirectConnect
    {
        $this->outputId = $outputId;

        return $this;
    }
}
