<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Parameter\Io;

use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Module\Hc\AutoComplete\Io\PortAutoComplete;

class PortParameter extends AutoCompleteParameter
{
    public function __construct(private PortAutoComplete $portAutoComplete, string $title = 'Port')
    {
        parent::__construct($title, $this->portAutoComplete);
    }
}
