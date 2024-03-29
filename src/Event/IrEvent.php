<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Event;

use GibsonOS\Core\Attribute\Event;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Manager\ReflectionManager;
use GibsonOS\Core\Service\EventService;
use GibsonOS\Core\Service\FcmService;
use GibsonOS\Module\Hc\Dto\Parameter\Ir\KeyParameter;
use GibsonOS\Module\Hc\Dto\Parameter\ModuleParameter;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Module\IrService;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

#[Event('IR')]
#[Event\ParameterOption('slave', 'typeHelper', 'ir')]
#[Event\ParameterOption('module', 'typeHelper', 'ir')]
class IrEvent extends AbstractHcEvent
{
    #[Event\Trigger('Nach empfangen von IR Tasten', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
        ['key' => 'key', 'className' => KeyParameter::class],
    ])]
    public const READ_IR = 'afterReadIr';

    #[Event\Trigger('Vor senden von IR Tasten', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
    ])]
    public const BEFORE_WRITE_IR = 'beforeWriteIr';

    #[Event\Trigger('Nach senden von IR Tasten', [
        ['key' => 'slave', 'className' => ModuleParameter::class],
    ])]
    public const AFTER_WRITE_IR = 'afterWriteIr';

    public function __construct(
        EventService $eventService,
        ReflectionManager $reflectionManager,
        TypeRepository $typeRepository,
        LoggerInterface $logger,
        FcmService $fcmService,
        private readonly IrService $irService,
    ) {
        parent::__construct($eventService, $reflectionManager, $typeRepository, $logger, $fcmService, $this->irService);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws WriteException
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Event\Method('Taste senden')]
    public function sendKey(
        #[Event\Parameter(ModuleParameter::class)]
        Module $slave,
        #[Event\Parameter(KeyParameter::class)]
        Key $key,
    ): void {
        $this->irService->sendKeys($slave, [$key]);
    }
}
