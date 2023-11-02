<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\Response\AjaxResponse;
use GibsonOS\Core\Service\Response\TwigResponse;
use GibsonOS\Module\Hc\Repository\BlueprintRepository;
use GibsonOS\Module\Hc\Store\BlueprintStore;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;

class BlueprintController extends AbstractController
{
    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SelectError
     * @throws ClientException
     * @throws RecordException
     */
    public function getIndex(
        BlueprintStore $blueprintStore,
        int $limit = 100,
        int $start = 0,
        array $sort = [],
    ): AjaxResponse {
        $blueprintStore
            ->setLimit($limit, $start)
            ->setSortByExt($sort)
        ;

        return $this->returnSuccess($blueprintStore->getList(), $blueprintStore->getCount());
    }

    #[CheckPermission([Permission::READ])]
    public function getSvg(
        int $id,
        BlueprintRepository $blueprintRepository,
        array $childrenTypes = [],
        bool $withDimensions = false,
    ): TwigResponse {
        $maxWidth = 0;
        $maxHeight = 0;
        $blueprint = $blueprintRepository->getExpanded($id, $childrenTypes);

        foreach ($blueprint->getGeometries() as $geometry) {
            $maxWidth = max($maxWidth, $geometry->getWidth() + $geometry->getLeft());
            $maxHeight = max($maxHeight, $geometry->getHeight() + $geometry->getTop());
        }

        return (new TwigResponse(
            $this->twigService,
            '@hc/blueprint.svg.twig',
            headers: ['Content-Type' => 'image/svg+xml'],
        ))
            ->setVariable('blueprint', $blueprint)
            ->setVariable('width', $maxWidth)
            ->setVariable('height', $maxHeight)
            ->setVariable('withDimensions', $withDimensions)
        ;
    }
}
