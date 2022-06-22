<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Parameter\Ir;

use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Module\Hc\AutoComplete\Ir\KeyAutoComplete;

class KeyParameter extends AutoCompleteParameter
{
    public function __construct(KeyAutoComplete $keyAutoComplete, string $title = 'Taste')
    {
        parent::__construct($title, $keyAutoComplete);
    }
}
