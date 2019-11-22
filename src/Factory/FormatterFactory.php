<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use DateTime;
use Exception;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Service\Formatter\AbstractFormatter;
use GibsonOS\Module\Hc\Service\Formatter\FormatterInterface;
use GibsonOS\Module\Hc\Service\Formatter\MasterFormatter;

class FormatterFactory extends AbstractSingletonFactory
{
    /**
     * @param array $log
     *
     * @throws FileNotFound
     * @throws Exception
     *
     * @return AbstractFormatter
     */
    public static function createByLog(array $log): FormatterInterface
    {
        if (empty($log['module_id'])) {
            return self::getMasterFormatter();
        }

        return self::getModuleFormatter($log);
    }

    /**
     * @return MasterFormatter
     */
    private static function getMasterFormatter(): MasterFormatter
    {
        return new MasterFormatter();
    }

    /**
     * @param array $log
     *
     * @throws FileNotFound
     * @throws Exception
     *
     * @return AbstractFormatter
     */
    private static function getModuleFormatter(array $log): AbstractFormatter
    {
        $className = 'GibsonOS\\Module\\Hc\\Utility\\Formatter\\' . ucfirst($log['helper']);

        if (!class_exists($className)) {
            throw new FileNotFound('Formatter ' . $log['type_name'] . ' nicht gefunden!');
        }

        $type = (new Type())
            ->setId($log['type_id'])
            ->setName($log['type_name'])
            ->setHelper($log['helper'])
            ->setNetwork($log['network'])
            ->setHertz($log['type_hertz'])
            ->setUiSettings($log['ui_settings']);

        $module = (new Module())
            ->setId($log['module_id'])
            ->setDeviceId($log['device_id'])
            ->setName($log['name'])
            ->setTypeId($log['type_id'])
            ->setConfig($log['config'])
            ->setHertz($log['hertz'])
            ->setAddress($log['address'])
            ->setIp($log['ip'])
            ->setMasterId($log['master_id'])
            ->setOffline($log['offline'])
            ->setAdded(new DateTime($log['module_added']))
            ->setModified(new DateTime($log['module_modified']))
            ->setType($type);

        return new $className(
            $module,
            $log['direction'],
            $log['type'],
            $log['data'],
            $log['command'],
            $log['id']
        );
    }
}
