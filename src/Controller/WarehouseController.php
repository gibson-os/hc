<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use chillerlan\QRCode\QRCode;
use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetEnv;
use GibsonOS\Core\Attribute\GetMappedModels;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetObjects;
use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Dto\File;
use GibsonOS\Core\Exception\AbstractException;
use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\DateTimeError;
use GibsonOS\Core\Exception\DeleteError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\DeleteError as ModelDeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\RequestError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\Response\FileResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
use GibsonOS\Module\Hc\Repository\Warehouse\BoxRepository;
use GibsonOS\Module\Hc\Service\Warehouse\BoxService;
use GibsonOS\Module\Hc\Service\Warehouse\ItemService;
use GibsonOS\Module\Hc\Store\Warehouse\BoxStore;
use JsonException;
use ReflectionException;

class WarehouseController extends AbstractController
{
    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ReflectionException
     */
    #[CheckPermission(Permission::READ)]
    public function index(
        BoxStore $boxStore,
        #[GetModel(['id' => 'moduleId'])] Module $module,
    ): AjaxResponse {
        $boxStore->setModule($module);

        return $this->returnSuccess($boxStore->getList(), $boxStore->getCount());
    }

    /**
     * @param Box[]  $boxes
     * @param File[] $newFiles
     * @param File[] $newImages
     *
     * @throws JsonException
     * @throws ReflectionException
     * @throws RequestError
     * @throws SaveError
     * @throws SelectError
     * @throws CreateError
     * @throws DeleteError
     * @throws FileNotFound
     * @throws GetError
     * @throws ModelDeleteError
     * @throws SetError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function save(
        ModelManager $modelManager,
        ItemService $itemService,
        BoxRepository $boxRepository,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModels(Box::class)] array $boxes,
        #[GetObjects(File::class)] array $newFiles = [],
        #[GetObjects(File::class)] array $newImages = [],
    ): AjaxResponse {
        $rawBoxes = JsonUtility::decode($this->requestService->getRequestValue('boxes'));

        foreach ($boxes as $boxIndex => $box) {
            $rawBox = $rawBoxes[$boxIndex];

            if (mb_strlen($box->getUuid()) !== 8) {
                $box->setUuid($boxRepository->getFreeUuid());
            }

            foreach ($box->getItems() as $itemIndex => $item) {
                $rawItem = $rawBox['items'][$itemIndex];

                if (isset($rawItem['imageIndex']) && is_int($rawItem['imageIndex'])) {
                    $itemService->saveImage($item, $newImages[$rawItem['imageIndex']]);
                }

                $itemService->deleteFilesNotIn($item);
                $itemService->saveFiles($item, $rawItem, $newFiles);
                $itemService->saveTags($item);
            }

            $modelManager->save($box);
        }

        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ)]
    public function qrCode(
        #[GetEnv('WEB_URL')] string $webUrl,
        #[GetModel] Box $box
    ): FileResponse {
        $fileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qrCode' . md5((string) mt_rand());
        (new QRCode())->render($webUrl . '/hc/warehouse/box/uuid/' . $box->getUuid(), $fileName);

        return (new FileResponse($this->requestService, $fileName))
            ->setType('image/png')
            ->setDisposition('inline')
        ;
    }

    #[CheckPermission(Permission::READ)]
    public function image(
        ItemService $itemService,
        #[GetModel] ?Box\Item $item
    ): FileResponse {
        $image = realpath(
            __DIR__ . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'assets' . DIRECTORY_SEPARATOR .
            'img' . DIRECTORY_SEPARATOR .
            'placeholder-image.png'
        );
        $mimeType = 'image/png';

        if ($item !== null) {
            $itemImage = $item->getImage();

            if ($itemImage !== null) {
                $image = $itemService->getFilePath() . $itemImage;
                $mimeType = $item->getImageMimeType() ?? $mimeType;
            }
        }

        return (new FileResponse($this->requestService, $image))
            ->setDisposition('inline')
            ->setType($mimeType)
        ;
    }

    #[CheckPermission(Permission::READ)]
    public function download(
        #[GetSetting('file_path')] Setting $filePath,
        #[GetModel] Box\Item\File $file
    ): FileResponse {
        return (new FileResponse(
            $this->requestService,
            $filePath->getValue() . 'warehouse' . DIRECTORY_SEPARATOR . $file->getFileName()
        ))
            ->setType($file->getMimeType())
        ;
    }

    /**
     * @throws SaveError
     * @throws AbstractException
     * @throws DateTimeError
     */
    public function show(
        BoxService $boxService,
        #[GetModel(['id' => 'id', 'module_id' => 'moduleId'])] Box $box,
        int $red = 255,
        int $green = 255,
        int $blue = 255,
        int $fadeIn = 0,
        int $blink = 0,
    ): AjaxResponse {
        $boxService->showLeds([$box], $red, $green, $blue, $fadeIn, $blink);

        return $this->returnSuccess();
    }
}
