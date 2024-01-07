<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Warehouse;

use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Dto\File;
use GibsonOS\Core\Exception\CreateError;
use GibsonOS\Core\Exception\DeleteError;
use GibsonOS\Core\Exception\FileNotFound;
use GibsonOS\Core\Exception\GetError;
use GibsonOS\Core\Exception\Model\DeleteError as ModelDeleteError;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\SetError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Service\FileService;
use GibsonOS\Module\Hc\Model\Warehouse\Box\Item;
use GibsonOS\Module\Hc\Repository\Warehouse\Box\Item\FileRepository;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class ItemService
{
    private const FILE_DIR = 'warehouse';

    public function __construct(
        private readonly FileService $fileService,
        private readonly FileRepository $fileRepository,
        private readonly ModelManager $modelManager,
        #[GetSetting('file_path')]
        private readonly Setting $filePath,
    ) {
    }

    /**
     * @throws DeleteError
     * @throws FileNotFound
     * @throws GetError
     * @throws CreateError
     * @throws SetError
     */
    public function saveImage(Item $item, File $newImage): void
    {
        $itemImage = $item->getImage();

        if ($itemImage !== null) {
            $this->fileService->delete($this->getFilePath() . $itemImage);
        }

        $fileName = $this->getFileName($newImage);
        $this->fileService->move($newImage->getTmpName(), $this->getFilePath() . $fileName);
        $item->setImageMimeType($newImage->getType());
        $item->setImage($fileName);
    }

    /**
     * @param File[] $newFiles
     *
     * @throws CreateError
     * @throws DeleteError
     * @throws FileNotFound
     * @throws GetError
     * @throws SetError
     */
    public function saveFiles(Item $item, array $rawItem, array $newFiles): void
    {
        foreach ($item->getFiles() as $fileIndex => $file) {
            $rawFile = $rawItem['files'][$fileIndex];

            if (!isset($rawFile['fileIndex']) || !is_int($rawFile['fileIndex'])) {
                continue;
            }

            $newFile = $newFiles[$rawFile['fileIndex']];
            $file->setMimeType($newFile->getType());
            $fileName = $this->getFileName($newFile);
            $this->fileService->move(
                $newFile->getTmpName(),
                $this->getFilePath() . $fileName,
            );
            $file->setFileName($fileName);
        }
    }

    /**
     * @throws JsonException
     * @throws SaveError
     * @throws ReflectionException
     */
    public function saveTags(Item $item): void
    {
        foreach ($item->getTags() as $tag) {
            $tagTag = $tag->getTag();

            if ($tagTag->getId() !== null && $tagTag->getId() !== 0) {
                continue;
            }

            $this->modelManager->save($tagTag);
            $tag->setTag($tagTag);
        }
    }

    /**
     * @throws DeleteError
     * @throws FileNotFound
     * @throws GetError
     * @throws JsonException
     * @throws ModelDeleteError
     * @throws ReflectionException
     * @throws ClientException
     * @throws RecordException
     */
    public function deleteFilesNotIn(Item $item): void
    {
        if ($item->getId() === 0 || $item->getId() === null) {
            return;
        }

        foreach ($this->fileRepository->getFilesNotIn($item) as $file) {
            $this->fileService->delete($this->getFilePath() . $file->getFileName());
            $this->modelManager->delete($file);
        }
    }

    public function getFilePath(): string
    {
        return sprintf(
            '%s%s%s',
            $this->filePath->getValue(),
            self::FILE_DIR,
            DIRECTORY_SEPARATOR,
        );
    }

    private function getFileName(File $file): string
    {
        return md5(
            $file->getName() .
            $file->getType() .
            $file->getSize() .
            $file->getTmpName(),
        );
    }
}
