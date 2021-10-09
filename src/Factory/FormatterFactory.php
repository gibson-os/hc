<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use Exception;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Formatter\AbstractFormatter;
use GibsonOS\Module\Hc\Formatter\FormatterInterface;
use GibsonOS\Module\Hc\Formatter\MasterFormatter;
use GibsonOS\Module\Hc\Model\Log;

class FormatterFactory
{
    public function __construct(private ServiceManagerService $serviceManagerService)
    {
    }

    /**
     * @throws Exception
     */
    public function get(Log $log): FormatterInterface
    {
        if (empty($log->getModuleId())) {
            /** @var MasterFormatter $masterFormatter */
            $masterFormatter = $this->serviceManagerService->get(MasterFormatter::class);

            return $masterFormatter;
        }

        return $this->getModuleFormatter($log);
    }

    /**
     * @throws DateTimeError
     * @throws FactoryError
     */
    private function getModuleFormatter(Log $log): AbstractFormatter
    {
        $module = $log->getModule();

        if ($module === null) {
            throw new FactoryError('Log model has no module!');
        }

        $className =
            'GibsonOS\\Module\\Hc\\Formatter\\' .
            ucfirst($module->getType()->getHelper()) . 'Formatter'
        ;

        /** @var AbstractFormatter $formatter */
        $formatter = $this->serviceManagerService->get($className);

        return $formatter;
    }
}
