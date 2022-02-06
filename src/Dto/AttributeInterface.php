<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto;

use GibsonOS\Module\Hc\Model\Module;

interface AttributeInterface
{
    public const SUB_ID_NULLABLE = false;

    public function getSubId(): ?int;

    public function getTypeName(): string;

    public function getModule(): ?Module;
}
