<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Model\Module;
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
        #[GetModel(['id' => 'moduleId'])] Module $module,
        int $command,
        string $dataFormat,
        int $length
    ): AjaxResponse {
        $data = $blankService->read($module, $command, $length);
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
        #[GetModel(['id' => 'moduleId'])] Module $module,
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

        if ($isHcData) {
            $blankService->write($module, $command, $data);
        } else {
            $blankService->writeRaw($module, $command, $data);
        }

        return $this->returnSuccess($transformService->asciiToBin($data));
    }
}
