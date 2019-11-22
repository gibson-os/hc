<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Formatter;

interface FormatterInterface
{
    /**
     * @return string|null
     */
    public function render(): ?string;

    /**
     * @return string|null
     */
    public function text(): ?string;

    /**
     * @return int|string|null
     */
    public function command();
}
