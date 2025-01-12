<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use Exception;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Manager\ServiceManager;
use GibsonOS\Module\Hc\Formatter\AbstractFormatter;
use GibsonOS\Module\Hc\Formatter\FormatterInterface;
use GibsonOS\Module\Hc\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Model\Log;

class FormatterFactory
{
    /**
     * @throws Exception
     */
    public function get(Log $log): FormatterInterface
    {
        if (in_array($log->getModuleId(), [null, 0], true)) {
            return $this->serviceManager->get(MasterFormatter::class);
        }

        return $this->getModuleFormatter($log);
    }

    public function __construct(private readonly ServiceManager $serviceManager)
    {
    }

    /**
     * @throws FactoryError
     */
    private function getModuleFormatter(Log $log): AbstractFormatter
    {
        $module = $log->getModule();

        if ($module === null) {
            throw new FactoryError('Log model has no module!');
        }

        /** @var class-string $className */
        $className =
            'GibsonOS\\Module\\Hc\\Formatter\\' .
            ucfirst($module->getType()->getHelper()) . 'Formatter'
        ;

        /** @var AbstractFormatter $formatter */
        $formatter = $this->serviceManager->get($className);

        return $formatter;
    }
}
