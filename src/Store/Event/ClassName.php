<?php
namespace GibsonOS\Module\Hc\Store\Event;

use GibsonOS\Core\Store\AbstractStore;
use GibsonOS\Core\Utility\Dir;
use GibsonOS\Core\Utility\File;
use GibsonOS\Module\Hc\Service\Event\Describer\DescriberInterface;

class ClassName extends AbstractStore
{
    /**
     * @var array[]
     */
    private $list = [];

    /**
     * @return array[]
     */
    public function getList()
    {
        $this->generateList();

        return $this->list;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->getList());
    }

    private function generateList()
    {
        if (count($this->list) !== 0) {
            return;
        }

        $namespace = 'GibsonOS\\Module\\Hc\\Service\\Event\\Describer\\';
        $eventDescriberDir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'Service' . DIRECTORY_SEPARATOR .
            'Event' . DIRECTORY_SEPARATOR .
            'Describer' . DIRECTORY_SEPARATOR;
        $classNames = [];

        foreach (glob(Dir::escapeForGlob($eventDescriberDir) . '*.php') as $classPath) {
            $className = str_replace('.php', '', File::getFilename($classPath));

            if (mb_strpos($className, 'Abstract') !== false) {
                continue;
            }

            if (mb_strpos($className, 'Interface') !== false) {
                continue;
            }

            $classNameWithNamespace = $namespace . $className;
            $class = new $classNameWithNamespace();

            if (!$class instanceof DescriberInterface) {
                continue;
            }

            $classNames[] = [
                'className' => $className,
                'title' => $class->getTitle()
            ];
        }

        $this->list = $classNames;
    }
}