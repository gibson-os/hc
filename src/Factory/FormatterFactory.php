<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use Exception;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Service\ServiceManagerService;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Formatter\AbstractFormatter;
use GibsonOS\Module\Hc\Service\Formatter\FormatterInterface;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;

class FormatterFactory
{
    /**
     * @var ServiceManagerService
     */
    private $serviceManagerService;

    public function __construct(ServiceManagerService $serviceManagerService)
    {
        $this->serviceManagerService = $serviceManagerService;
    }

    /**
     * @throws FileNotFound
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
     * @throws FileNotFound
     * @throws Exception
     */
    private function getModuleFormatter(Log $log): AbstractFormatter
    {
        $className =
            'GibsonOS\\Module\\Hc\\Service\\Formatter\\' .
            ucfirst($log->getModule()->getType()->getHelper()) . 'Formatter'
        ;

        /** @var AbstractFormatter $formatter */
        $formatter = (new ServiceManagerService())->get($className);

        return $formatter;
    }
}
