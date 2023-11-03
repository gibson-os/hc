<?php
declare(strict_types=1);

namespace GibsonOS\Test\Functional\Hc\Controller;

use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Module\Hc\Controller\BlueprintController;
use GibsonOS\Module\Hc\Enum\Blueprint\Geometry;
use GibsonOS\Module\Hc\Enum\Blueprint\Type;
use GibsonOS\Module\Hc\Model\Blueprint;
use GibsonOS\Module\Hc\Repository\BlueprintRepository;
use GibsonOS\Module\Hc\Store\BlueprintStore;
use GibsonOS\Test\Functional\Hc\HcFunctionalTest;
use Twig\Loader\FilesystemLoader;

class BlueprintControllerTest extends HcFunctionalTest
{
    private BlueprintController $blueprintController;

    protected function _before(): void
    {
        parent::_before();

        $this->blueprintController = $this->serviceManager->get(BlueprintController::class);
    }

    public function testGetIndex(): void
    {
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $blueprintDent = (new Blueprint($this->modelWrapper))->setName('dent');
        $blueprintArthur = (new Blueprint($this->modelWrapper))->setName('arthur');
        $modelManager->saveWithoutChildren($blueprintDent);
        $modelManager->saveWithoutChildren($blueprintArthur);
        $expected = [
            [
                'id' => $blueprintArthur->getId(),
                'name' => 'arthur',
            ],
            [
                'id' => $blueprintDent->getId(),
                'name' => 'dent',
            ],
        ];

        $response = $this->blueprintController->getIndex($this->serviceManager->get(BlueprintStore::class));
        $this->checkSuccessResponse($response, $expected);
    }

    public function testGetSvg(): void
    {
        $templatePath = realpath(
            dirname(__FILE__) . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'template' . DIRECTORY_SEPARATOR,
        ) . DIRECTORY_SEPARATOR;
        $twigService = $this->serviceManager->get(TwigService::class);
        $loader = new FilesystemLoader();
        $loader->addPath($templatePath, 'hc');
        $twigService->getTwig()->setLoader($loader);
        $blueprint = (new Blueprint($this->modelWrapper))
            ->setName('arthur')
            ->setGeometries([
                (new Blueprint\Geometry($this->modelWrapper))
                    ->setTop(42)
                    ->setLeft(24)
                    ->setWidth(420)
                    ->setHeight(240)
                    ->setType(Geometry::RECTANGLE),
            ])
        ;
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->save($blueprint);

        $response = $this->blueprintController->getSvg(
            $blueprint->getId(),
            $this->serviceManager->get(BlueprintRepository::class),
        );

        $expected = '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <rect
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        width="420"
    height="240"
    x="24"
    y="42"
/>        </g></svg>';
        $this->assertEquals($expected, $response->getBody());
    }

    public function testGetSvgWithDimensions(): void
    {
        $templatePath = realpath(
            dirname(__FILE__) . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'template' . DIRECTORY_SEPARATOR,
        ) . DIRECTORY_SEPARATOR;
        $twigService = $this->serviceManager->get(TwigService::class);
        $loader = new FilesystemLoader();
        $loader->addPath($templatePath, 'hc');
        $twigService->getTwig()->setLoader($loader);
        $blueprint = (new Blueprint($this->modelWrapper))
            ->setName('arthur')
            ->setGeometries([
                (new Blueprint\Geometry($this->modelWrapper))
                    ->setTop(42)
                    ->setLeft(24)
                    ->setWidth(420)
                    ->setHeight(240)
                    ->setType(Geometry::LINE),
            ])
        ;
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->save($blueprint);

        $response = $this->blueprintController->getSvg(
            $blueprint->getId(),
            $this->serviceManager->get(BlueprintRepository::class),
            withDimensions: true,
        );

        $expected = '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                                                    <path
        style="fill:none;stroke:#000000;stroke-width:1"
        d="M 24,12 420,240,"
    />
    <path
        style="fill:none;stroke:#000000;stroke-width:1"
        d="M
            24,
            -8            v 40
            "
    />
    <path
        style="fill:none;stroke:#000000;stroke-width:1"
        d="M
            444,
            232            v 40
            "
    />
    <text
        style="text-align:center;fill:#000000;stroke:#000000;font-size:2em;"
        x="244"
        y="112">
            420mm
    </text>
        </g></svg>';
        $this->assertEquals($expected, $response->getBody());
    }

    /**
     * @dataProvider getData
     */
    public function testGetSvgWithTypes(array $types, string $expected): void
    {
        $templatePath = realpath(
            dirname(__FILE__) . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'template' . DIRECTORY_SEPARATOR,
        ) . DIRECTORY_SEPARATOR;
        $twigService = $this->serviceManager->get(TwigService::class);
        $loader = new FilesystemLoader();
        $loader->addPath($templatePath, 'hc');
        $twigService->getTwig()->setLoader($loader);
        $blueprint = (new Blueprint($this->modelWrapper))
            ->setName('arthur')
            ->setGeometries([
                (new Blueprint\Geometry($this->modelWrapper))
                    ->setTop(42)
                    ->setLeft(24)
                    ->setWidth(420)
                    ->setHeight(240)
                    ->setType(Geometry::LINE),
            ])
        ;
        $modelManager = $this->serviceManager->get(ModelManager::class);
        $modelManager->save($blueprint);
        $room = (new Blueprint($this->modelWrapper))
            ->setName('dent')
            ->setParent($blueprint)
            ->setLeft(42)
            ->setType(Type::ROOM)
            ->setGeometries([
                (new Blueprint\Geometry($this->modelWrapper))
                    ->setTop(42)
                    ->setLeft(24)
                    ->setWidth(420)
                    ->setHeight(240)
                    ->setType(Geometry::LINE),
            ])
        ;
        $modelManager->save($room);
        $modelManager->save(
            (new Blueprint($this->modelWrapper))
                ->setName('ford')
                ->setParent($room)
                ->setTop(42)
                ->setType(Type::FURNISHING)
                ->setGeometries([
                    (new Blueprint\Geometry($this->modelWrapper))
                        ->setTop(42)
                        ->setLeft(24)
                        ->setWidth(420)
                        ->setHeight(240)
                        ->setType(Geometry::LINE),
                ])
        );
        $modelManager->save(
            (new Blueprint($this->modelWrapper))
                ->setName('prefect')
                ->setParent($blueprint)
                ->setTop(42)
                ->setType(Type::FURNISHING)
                ->setGeometries([
                    (new Blueprint\Geometry($this->modelWrapper))
                        ->setTop(42)
                        ->setLeft(24)
                        ->setWidth(420)
                        ->setHeight(240)
                        ->setType(Geometry::ELLIPSE),
                ])
        );
        $modelManager->save(
            (new Blueprint($this->modelWrapper))
                ->setName('marvin')
                ->setParent($blueprint)
                ->setTop(42)
                ->setType(Type::MODULE)
                ->setGeometries([
                    (new Blueprint\Geometry($this->modelWrapper))
                        ->setTop(42)
                        ->setLeft(24)
                        ->setWidth(420)
                        ->setHeight(240)
                        ->setType(Geometry::RECTANGLE),
                ])
        );

        $response = $this->blueprintController->getSvg(
            $blueprint->getId(),
            $this->serviceManager->get(BlueprintRepository::class),
            $types,
        );
        $this->assertEquals($expected, $response->getBody());
    }

    public function getData(): array
    {
        return [
            'all' => [[], '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                <g
    id="blueprint2"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry2"
        d="M 66,42 420,240,"
/>
                <g
    id="blueprint3"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry3"
        d="M 66,84 420,240,"
/>
        </g>    </g>    </g></svg>'],
            'frame' => [[Type::FRAME->name], '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                <g
    id="blueprint2"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry2"
        d="M 66,42 420,240,"
/>
                <g
    id="blueprint3"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry3"
        d="M 66,84 420,240,"
/>
        </g>    </g>            <g
    id="blueprint4"
    >
            <ellipse
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry4"
        cx="234"
    cy="204"
    rx="210"
    ry="120"
/>        </g>            <g
    id="blueprint5"
    >
            <rect
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry5"
        width="420"
    height="240"
    x="24"
    y="84"
/>        </g>    </g></svg>'],
            'room' => [[Type::ROOM->name], '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                <g
    id="blueprint2"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry2"
        d="M 66,42 420,240,"
/>
                <g
    id="blueprint3"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry3"
        d="M 66,84 420,240,"
/>
        </g>    </g>    </g></svg>'],
            'furnishing' => [[Type::FURNISHING->name], '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                <g
    id="blueprint4"
    >
            <ellipse
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry4"
        cx="234"
    cy="204"
    rx="210"
    ry="120"
/>        </g>    </g></svg>'],
            'room and furnishing' => [[Type::ROOM->name, Type::FURNISHING->name], '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                <g
    id="blueprint2"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry2"
        d="M 66,42 420,240,"
/>
                <g
    id="blueprint3"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry3"
        d="M 66,84 420,240,"
/>
        </g>    </g>    </g></svg>'],
            'module' => [[Type::MODULE->name], '<svg
    width="100%"
    height="100%"
    viewBox="0 0 444 282"
    xmlns="http://www.w3.org/2000/svg"
>
    <g
    id="blueprint1"
    >
            <path
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry1"
        d="M 24,42 420,240,"
/>
                <g
    id="blueprint5"
    >
            <rect
    style="fill:transparent;stroke:#000000;stroke-width:1"
    id="geometry5"
        width="420"
    height="240"
    x="24"
    y="84"
/>        </g>    </g></svg>'],
        ];
    }
}
