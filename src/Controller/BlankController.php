<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Server\ReceiveError;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Module\Hc\Exception\WriteException;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Service\Module\BlankService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class BlankController extends AbstractController
{
    private const DATA_FORMAT_HEX = 'hex';

    private const DATA_FORMAT_BIN = 'bin';

    private const DATA_FORMAT_INT = 'int';

    /**
     * @throws AbstractException
     * @throws FactoryError
     * @throws GetError
     * @throws JsonException
     * @throws ReceiveError
     * @throws ReflectionException
     * @throws SaveError
     * @throws ClientException
     * @throws RecordException
     */
    #[CheckPermission([Permission::READ])]
    public function get(
        BlankService $blankService,
        TransformService $transformService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        int $command,
        string $dataFormat,
        int $length,
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
     * @throws FactoryError
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws WriteException
     */
    #[CheckPermission([Permission::WRITE])]
    public function post(
        BlankService $blankService,
        TransformService $transformService,
        #[GetModel(['id' => 'moduleId'])]
        Module $module,
        int $command,
        string $dataFormat,
        string $data,
        bool $isHcData,
    ): AjaxResponse {
        switch ($dataFormat) {
            case self::DATA_FORMAT_HEX:
                $data = $transformService->hexToAscii($data);

                break;
            case self::DATA_FORMAT_BIN:
                $data = $transformService->binToAscii($data);

                break;
            case self::DATA_FORMAT_INT:
                // $data = $transformService->intToAscii($data);
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
