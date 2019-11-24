<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use Exception;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Factory\Formatter\MasterFactory;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Service\Formatter\AbstractFormatter;
use GibsonOS\Module\Hc\Service\Formatter\FormatterInterface;

class FormatterFactory
{
    /**
     * @throws FileNotFound
     * @throws Exception
     */
    public static function create(Log $log): FormatterInterface
    {
        if (empty($log->getModuleId())) {
            return MasterFactory::create();
        }

        return self::getModuleFormatter($log);
    }

    /**
     * @throws FileNotFound
     * @throws Exception
     */
    private static function getModuleFormatter(Log $log): AbstractFormatter
    {
        $className =
            'GibsonOS\\Module\\Hc\\Factory\\Formatter\\' .
            ucfirst($log->getModule()->getType()->getHelper()) . 'Factory'
        ;

        if (!class_exists($className)) {
            throw new FileNotFound(sprintf('Formatter %s nicht gefunden!', $className));
        }

        /** @var AbstractSingletonFactory $className */
        /** @var AbstractFormatter $formatter */
        $formatter = $className::create();

        return $formatter;
    }
}
