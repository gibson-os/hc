<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service;

use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;

class HandshakeService extends AbstractService
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

    public function __construct(ModuleRepository $moduleRepository, TypeRepository $typeRepository)
    {
        $this->moduleRepository = $moduleRepository;
        $this->typeRepository = $typeRepository;
    }
}
