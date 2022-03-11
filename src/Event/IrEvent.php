<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Dto\Parameter\Ir\KeyParameter;
use GibsonOS\Module\Hc\Dto\Parameter\SlaveParameter;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Slave\IrService;

#[Event('IR')]
#[Event\ParameterOption('slave', 'typeHelper', 'ir')]
class IrEvent
{
    #[Event\Trigger('Nach empfangen von IR Tasten', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
        ['key' => 'key', 'className' => KeyParameter::class],
    ])]
    public const READ_IR = 'afterReadPort';

    #[Event\Trigger('Vor senden von IR Tasten', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const BEFORE_WRITE_IR = 'beforeReadPort';

    #[Event\Trigger('Nach senden von IR Tasten', [
        ['key' => 'slave', 'className' => SlaveParameter::class],
    ])]
    public const AFTER_WRITE_IR = 'afterReadPort';

    public function __construct(private IrService $irService)
    {
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     */
    #[Event\Method('Taste senden')]
    public function sendKey(
        #[Event\Parameter(SlaveParameter::class)] Module $slave,
        #[Event\Parameter(KeyParameter::class)] Key $key,
    ): void {
        $this->irService->sendKeys($slave, [$key]);
    }
}
