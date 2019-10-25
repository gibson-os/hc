<?php
namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Utility\Event\CodeGenerator;
use GibsonOS\Module\Hc\Model\Master as MasterModel;
use GibsonOS\Module\Hc\Repository\Event\Trigger as TriggerRepository;
use GibsonOS\Module\Hc\Repository\Master as MasterRepository;
use GibsonOS\Module\Hc\Service\Event;
use GibsonOS\Module\Hc\Service\Master as MasterService;
use GibsonOS\Module\Hc\Service\Server as ServerService;

class Master
{
    /**
     * @param MasterModel $masterModel
     * @param ServerService|null $server
     * @return MasterService
     * @throws FileNotFound
     */
    public static function create(MasterModel $masterModel, ServerService $server = null): MasterService
    {
        if (is_null($server)) {
            $server = Server::create($masterModel->getProtocol());
        }

        $event = new Event();
        $triggers = TriggerRepository::getByMasterId($masterModel->getId());

        foreach ($triggers as $trigger) {
            $event->add(
                $trigger->getTrigger(),
                CodeGenerator::generateByElements($trigger->getEvent()->getElements())
            );
        }

        $master = new MasterService($masterModel, $server, $event);

        return $master;
    }

    /**
     * @param int $address
     * @param string $protocol
     * @param ServerService|null $server
     * @return MasterService
     * @throws FileNotFound
     * @throws SelectError
     */
    public static function createByAddress(int $address, string $protocol, ServerService $server = null): MasterService
    {
        return self::create(MasterRepository::getByAddress($address, $protocol), $server);
    }

    /**
     * @param int $id
     * @param ServerService|null $server
     * @return MasterService
     * @throws FileNotFound
     * @throws SelectError
     */
    public static function createById(int $id, ServerService $server = null): MasterService
    {
        return self::create(MasterRepository::getById($id), $server);
    }
}