<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Factory;

use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Utility\Event\CodeGeneratorService;
use GibsonOS\Module\Hc\Model\Master as MasterModel;
use GibsonOS\Module\Hc\Repository\Event\Trigger as TriggerRepository;
use GibsonOS\Module\Hc\Repository\Master as MasterRepository;
use GibsonOS\Module\Hc\Service\EventService;
use GibsonOS\Module\Hc\Service\MasterService as MasterService;
use GibsonOS\Module\Hc\Service\ServerService as ServerService;

class Master
{
    /**
     * @param MasterModel        $masterModel
     * @param ServerService|null $server
     *
     * @throws FileNotFound
     *
     * @return MasterService
     */
    public static function create(MasterModel $masterModel, ServerService $server = null): MasterService
    {
        if ($server === null) {
            $server = Server::create($masterModel->getProtocol());
        }

        $event = new EventService();
        $triggers = TriggerRepository::getByMasterId($masterModel->getId());

        foreach ($triggers as $trigger) {
            $event->add(
                $trigger->getTrigger(),
                CodeGeneratorService::generateByElements($trigger->getEvent()->getElements())
            );
        }

        $master = new MasterService($masterModel, $server, $event);

        return $master;
    }

    /**
     * @param int                $address
     * @param string             $protocol
     * @param ServerService|null $server
     *
     * @throws FileNotFound
     * @throws SelectError
     *
     * @return MasterService
     */
    public static function createByAddress(int $address, string $protocol, ServerService $server = null): MasterService
    {
        return self::create(MasterRepository::getByAddress($address, $protocol), $server);
    }

    /**
     * @param int                $id
     * @param ServerService|null $server
     *
     * @throws FileNotFound
     * @throws SelectError
     *
     * @return MasterService
     */
    public static function createById(int $id, ServerService $server = null): MasterService
    {
        return self::create(MasterRepository::getById($id), $server);
    }
}
