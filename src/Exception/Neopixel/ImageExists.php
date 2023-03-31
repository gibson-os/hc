<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Exception\Neopixel;

use GibsonOS\Core\Exception\AbstractException;
use Throwable;

class ImageExists extends AbstractException
{
    public function __construct(int $existingImageId, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->setTitle('Überschreiben?');
        $this->setType(AbstractException::QUESTION);
        $this->addButton('Ja', 'id', $existingImageId);
        $this->addButton('Nein');
    }
}
