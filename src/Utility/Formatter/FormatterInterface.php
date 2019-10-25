<?php
namespace GibsonOS\Module\Hc\Utility\Formatter;

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
     * @return int|null|string
     */
    public function command();
}