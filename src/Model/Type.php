<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Model;

use GibsonOS\Core\Model\AbstractModel;
use GibsonOS\Core\Model\AutoCompleteModelInterface;

/**
 * Class Type.
 *
 * @package GibsonOS\Module\Hc\Model
 */
class Type extends AbstractModel implements AutoCompleteModelInterface
{
    private ?int $id = null;

    private string $name;

    private string $helper;

    private int $network;

    private int $hertz;

    private bool $isHcSlave;

    private ?string $uiSettings = null;

    public static function getTableName(): string
    {
        return 'hc_type';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): Type
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Type
    {
        $this->name = $name;

        return $this;
    }

    public function getHelper(): string
    {
        return $this->helper;
    }

    public function setHelper(string $helper): Type
    {
        $this->helper = $helper;

        return $this;
    }

    public function getNetwork(): int
    {
        return $this->network;
    }

    public function setNetwork(int $network): Type
    {
        $this->network = $network;

        return $this;
    }

    public function getHertz(): int
    {
        return $this->hertz;
    }

    public function setHertz(int $hertz): Type
    {
        $this->hertz = $hertz;

        return $this;
    }

    public function getIsHcSlave(): bool
    {
        return $this->isHcSlave;
    }

    public function setIsHcSlave(bool $isHcSlave): Type
    {
        $this->isHcSlave = $isHcSlave;

        return $this;
    }

    public function getUiSettings(): ?string
    {
        return $this->uiSettings;
    }

    public function setUiSettings(?string $uiSettings): Type
    {
        $this->uiSettings = $uiSettings;

        return $this;
    }

    public function getAutoCompleteId(): int
    {
        return (int) $this->getId();
    }
}
