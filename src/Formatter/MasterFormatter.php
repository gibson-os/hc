<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

class MasterFormatter implements FormatterInterface
{
    /**
     * @return string|null
     */
    public function render(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function text(): ?string
    {
        return null;
    }

    /**
     * @return int|string|null
     */
    public function command()
    {
        return null;
    }
}
