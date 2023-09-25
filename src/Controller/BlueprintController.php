<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Controller;

use GibsonOS\Core\Attribute\CheckPermission;
use GibsonOS\Core\Controller\AbstractController;
use GibsonOS\Core\Enum\Permission;
use GibsonOS\Core\Service\Response\TwigResponse;
use GibsonOS\Module\Hc\Repository\BlueprintRepository;

class BlueprintController extends AbstractController
{
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
