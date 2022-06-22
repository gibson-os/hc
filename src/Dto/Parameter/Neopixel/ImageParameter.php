<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Parameter\Neopixel;

use GibsonOS\Core\Dto\Parameter\AutoCompleteParameter;
use GibsonOS\Module\Hc\AutoComplete\Neopixel\ImageAutoComplete;

class ImageParameter extends AutoCompleteParameter
{
    public function __construct(ImageAutoComplete $imageAutoComplete, string $title = 'Bild')
    {
        parent::__construct($title, $imageAutoComplete);
    }
}
