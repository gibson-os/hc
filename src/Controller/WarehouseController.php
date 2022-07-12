<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Attribute\GetMappedModels;
use GibsonOS\Core\Attribute\GetModel;
use GibsonOS\Core\Attribute\GetObjects;
use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Dto\File;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Exception\RequestError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Model\User\Permission;
use GibsonOS\Core\Service\FileService;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\Response\FileResponse;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Warehouse\Box;
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
     * @throws SaveError
     * @throws RequestError
     */
    #[CheckPermission(Permission::READ + Permission::MANAGE)]
    public function save(
        ModelManager $modelManager,
        FileService $fileService,
        #[GetSetting('file_path')] Setting $filePath,
        #[GetModel(['id' => 'moduleId'])] Module $module,
        #[GetMappedModels(Box::class)] array $boxes,
        #[GetObjects(File::class)] array $newFiles = [],
        #[GetObjects(File::class)] array $newImages = [],
    ): AjaxResponse {
        $newTags = [];
        $rawBoxes = JsonUtility::decode($this->requestService->getRequestValue('boxes'));

        foreach ($boxes as $boxIndex => $box) {
            $rawBox = $rawBoxes[$boxIndex];

            foreach ($box->getItems() as $itemIndex => $item) {
                $rawItem = $rawBox['items'][$itemIndex];

                if (is_int($rawItem['imageIndex'])) {
                    $newImage = $newImages[$rawItem['imageIndex']];
                    $item->setImageMimeType($newImage->getType());
                    $fileName = md5(
                        $newImage->getName() .
                        $newImage->getType() .
                        $newImage->getSize() .
                        $newImage->getTmpName()
                    );
                    $fileService->move(
                        $newImage->getTmpName(),
                        $filePath->getValue() . 'warehouse' . DIRECTORY_SEPARATOR . $fileName
                    );
                    $item->setImage($fileName);
                }

                foreach ($item->getFiles() as $fileIndex => $file) {
                    $rawFile = $rawItem['files'][$fileIndex];

                    if (!is_int($rawFile['fileIndex'])) {
                        continue;
                    }

                    $newFile = $newFiles[$rawFile['fileIndex']];
                    $file->setMimeType($newFile->getType());
                    $fileName = md5(
                        $newFile->getName() .
                        $newFile->getType() .
                        $newFile->getSize() .
                        $newFile->getTmpName()
                    );
                    $fileService->move(
                        $newFile->getTmpName(),
                        $filePath->getValue() . 'warehouse' . DIRECTORY_SEPARATOR . $fileName
                    );
                    $file->setFileName($fileName);
                }

                foreach ($item->getTags() as $tag) {
                    $tagTag = $tag->getTag();

                    if ($tagTag->getId() !== null && $tagTag->getId() !== 0) {
                        continue;
                    }

                    if (isset($newTags[$tagTag->getName()])) {
                        $tag->setTag($tagTag);

                        continue;
                    }

                    $modelManager->save($tagTag);
                    $newTags[$tagTag->getName()] = $tagTag;
                }
            }

            $modelManager->save($box);
        }

        return $this->returnSuccess();
    }

    #[CheckPermission(Permission::READ)]
    public function image(
        #[GetSetting('file_path')] Setting $filePath,
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
                $image = sprintf(
                    '%swarehouse%s%s',
                    $filePath->getValue(),
                    DIRECTORY_SEPARATOR,
                    $itemImage
                );
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
}
