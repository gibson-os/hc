<?php
namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Utility\Dir;
use GibsonOS\Core\Utility\Event\CodeGenerator;
use GibsonOS\Core\Utility\File;
use GibsonOS\Module\Hc\Model\Module as ModuleModel;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Repository\Attribute as AttributeRepository;
use GibsonOS\Module\Hc\Repository\Attribute\Value as ValueRepository;
use GibsonOS\Module\Hc\Repository\Module as ModuleRepository;
use GibsonOS\Module\Hc\Repository\Event\Trigger as TriggerRepository;
use GibsonOS\Module\Hc\Repository\Type;
use GibsonOS\Module\Hc\Service\Event;
use GibsonOS\Module\Hc\Service\Master as MasterService;
use GibsonOS\Module\Hc\Service\Slave\AbstractSlave;

class Slave
{
    /**
     * @param ModuleModel $slaveModel
     * @param MasterService|null $master
     * @return AbstractSlave
     * @throws FileNotFound
     * @throws SelectError
     */
    public static function create(ModuleModel $slaveModel, MasterService $master = null): AbstractSlave
    {
        $slaveModel->loadType();
        $ucFirstHelper =  ucfirst($slaveModel->getType()->getHelper());

        if (is_null($master)) {
            $slaveModel->loadMaster();
            $master = Master::create($slaveModel->getMaster());
        } else {
            $slaveModel->setMaster($master->getModel());
        }

        $className = 'GibsonOS\\Module\\Hc\\Service\\Slave\\' . $ucFirstHelper;

        if (!class_exists($className)) {
            throw new FileNotFound('Slave Service ' . $ucFirstHelper . ' nicht gefunden!');
        }

        $event = new Event();

        if ($slaveModel->getId()) {
            $triggers = TriggerRepository::getByModuleId($slaveModel->getId());

            foreach ($triggers as $trigger) {
                $event->add(
                    $trigger->getTrigger(),
                    CodeGenerator::generateByElements($trigger->getEvent()->getElements())
                );
            }
        }

        $attributeClasses = [];
        $attributeClassesDir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'Service' . DIRECTORY_SEPARATOR .
            'Attribute' . DIRECTORY_SEPARATOR .
            $ucFirstHelper . DIRECTORY_SEPARATOR;

        if (is_dir(($attributeClassesDir))) {
            $attributeNamespace = 'GibsonOS\\Module\\Hc\\Service\\Attribute\\' . $ucFirstHelper . '\\';
            $attributeRepository = new AttributeRepository();
            $valueRepository = new ValueRepository();

            foreach (glob(Dir::escapeForGlob($attributeClassesDir) . '*.php') as $classPath) {
                $attributeClassName = str_replace('.php', '', File::getFilename($classPath));

                if (mb_strpos($attributeClassName, 'Abstract') !== false) {
                    continue;
                }

                $attributeClassNameWithNamespace = $attributeNamespace . $attributeClassName;
                $attributeClass = new $attributeClassNameWithNamespace($slaveModel, $attributeRepository, $valueRepository);
                $attributeClasses[$attributeClassNameWithNamespace] = $attributeClass;
            }
        }

        return new $className($slaveModel, $master, $event, $attributeClasses);
    }

    /**
     * @param int $address
     * @param MasterService|null $master
     * @return AbstractSlave
     * @throws FileNotFound
     * @throws SelectError
     */
    public static function createByDefaultAddress(int $address, MasterService $master = null): AbstractSlave
    {
        $typeModel = Type::getByDefaultAddress($address);

        $slaveModel = new Module();
        $slaveModel->setType($typeModel);
        $slaveModel->setAddress($address);
        $slaveModel->setMaster($master->getModel());

        return self::create($slaveModel, $master);
    }

    /**
     * @param int $slaveId
     * @param string|null $helperName
     * @param MasterService|null $master
     * @return AbstractSlave
     * @throws FileNotFound
     * @throws GetError
     * @throws SelectError
     */
    public static function createBySlaveId(
        int $slaveId,
        string $helperName = null,
        MasterService $master = null
    ): AbstractSlave {
        $slaveModel = ModuleRepository::getById($slaveId);
        $slaveModel->loadType();

        if (
            !is_null($helperName) &&
            $slaveModel->getType()->getHelper() != $helperName
        ) {
            throw new GetError('Slave passt nicht zum Typ');
        }

        return self::create($slaveModel, $master);
    }

    /**
     * @param int $address
     * @param MasterService|null $master
     * @return AbstractSlave
     * @throws FileNotFound
     * @throws SelectError
     */
    public static function createBlank(int $address, MasterService $master = null): AbstractSlave
    {
        $typeModel = Type::getById(255);

        $slaveModel = new Module();
        $slaveModel->setType($typeModel);
        $slaveModel->setAddress($address);
        $slaveModel->setMaster($master->getModel());

        return self::create($slaveModel, $master);
    }
}