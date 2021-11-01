<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Service\Slave\BlankService;
use GibsonOS\Module\Hc\Service\TransformService;

class BlankController extends AbstractController
{
    private const DATA_FORMAT_HEX = 'hex';

    private const DATA_FORMAT_BIN = 'bin';

    private const DATA_FORMAT_INT = 'int';

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     * @throws ReceiveError
     */
    #[CheckPermission(Permission::READ)]
    public function read(
        BlankService $blankService,
        TransformService $transformService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $command,
        string $dataFormat,
        int $length
    ): AjaxResponse {
        $slave = $moduleRepository->getById($moduleId);
        $data = $blankService->read($slave, $command, $length);
        $data = match ($dataFormat) {
            self::DATA_FORMAT_HEX => $transformService->asciiToHex($data),
            self::DATA_FORMAT_BIN => $transformService->asciiToBin($data),
            self::DATA_FORMAT_INT => $transformService->asciiToUnsignedInt($data),
        };

        return $this->returnSuccess($data);
    }

    /**
     * @throws AbstractException
     * @throws SaveError
     * @throws SelectError
     */
    #[CheckPermission(Permission::WRITE)]
    public function write(
        BlankService $blankService,
        TransformService $transformService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $command,
        string $dataFormat,
        string $data,
        bool $isHcData
    ): AjaxResponse {
        switch ($dataFormat) {
            case self::DATA_FORMAT_HEX:
                $data = $transformService->hexToAscii($data);

                break;
            case self::DATA_FORMAT_BIN:
                $data = $transformService->binToAscii($data);

                break;
            case self::DATA_FORMAT_INT:
                //$data = $transformService->intToAscii($data);
                break;
        }

        $slave = $moduleRepository->getById($moduleId);

        if ($isHcData) {
            $blankService->write($slave, $command, $data);
        } else {
            $blankService->writeRaw($slave, $command, $data);
        }

        return $this->returnSuccess($transformService->asciiToBin($data));
    }
}
