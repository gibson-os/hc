<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\LoginRequired;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\PermissionDenied;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\PermissionService;
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
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SelectError
     */
    public function read(
        BlankService $blankService,
        TransformService $transformService,
        ModuleRepository $moduleRepository,
        int $moduleId,
        int $command,
        string $dataFormat,
        int $length
    ): AjaxResponse {
        $this->checkPermission(PermissionService::READ);

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
     * @throws DateTimeError
     * @throws LoginRequired
     * @throws PermissionDenied
     * @throws SaveError
     * @throws SelectError
     */
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
        $this->checkPermission(PermissionService::WRITE);

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
