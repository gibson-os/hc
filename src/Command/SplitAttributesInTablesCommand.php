<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Mapper\ModelMapper;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\ImageRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\LedRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use JsonException;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * @description TEMP split attributes in tables
 */
class SplitAttributesInTablesCommand extends AbstractCommand
{
    /**
     * @var Module[]
     */
    private array $modules = [];

    public function __construct(
        LoggerInterface $logger,
        private readonly TypeRepository $typeRepository,
        private readonly AttributeRepository $attributeRepository,
        private readonly SequenceRepository $sequenceRepository,
        private readonly ModelMapper $modelMapper,
        private readonly ModelManager $modelManager,
        private readonly ModuleRepository $moduleRepository,
        private readonly LedRepository $ledRepository,
        private readonly ImageRepository $imageRepository,
        private readonly AnimationRepository $animationRepository,
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    protected function run(): int
    {
        $this->splitNeopixel();

        return self::SUCCESS;
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function splitNeopixel(): void
    {
        $type = $this->typeRepository->getByHelperName('neopixel');
        $this->createLeds($type);
        $this->createImages($type);
        $this->createAnimations($type);
    }

    /**
     * @throws SelectError
     * @throws FactoryError
     * @throws MapperException
     * @throws SaveError
     * @throws JsonException
     * @throws ReflectionException
     */
    private function createLeds(Type $type): void
    {
        $leds = [];
        $modulesWithLeds = [];

        foreach ($this->attributeRepository->getByType($type, 'led') as $ledAttribute) {
            $moduleId = $ledAttribute->getModuleId() ?? 0;

            if ($modulesWithLeds[$moduleId] ?? count($this->ledRepository->getByModule($this->getModule($moduleId)))) {
                $modulesWithLeds[$moduleId] = $moduleId;

                continue;
            }

            $number = $ledAttribute->getSubId() ?? 0;

            if (!isset($leds[$moduleId])) {
                $leds[$moduleId] = [];
            }

            if (!isset($leds[$moduleId][$number])) {
                $leds[$moduleId][$number] = [
                    'module' => $ledAttribute->getModule(),
                    'number' => $number,
                ];
            }

            $leds[$moduleId][$number][$ledAttribute->getKey()] = $ledAttribute->getValues()[0]->getValue();
        }

        foreach ($leds as $moduleLeds) {
            foreach ($moduleLeds as $led) {
                $this->modelManager->save($this->modelMapper->mapToObject(Led::class, $led));
            }
        }
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function createImages(Type $type): void
    {
        foreach ($this->sequenceRepository->getByType($type, 0) as $sequence) {
            try {
                $this->imageRepository->getByName($this->getModule($sequence->getModuleId() ?? 0), $sequence->getName());

                continue;
            } catch (SelectError) {
                // do nothing
            }

            $image = (new Image())
                ->setName($sequence->getName())
                ->setModuleId($sequence->getModuleId() ?? 0)
            ;
            $element = $sequence->getElements()[0];

            foreach (JsonUtility::decode($element->getData()) ?? [] as $ledData) {
                $image->addLeds([
                    (new Image\Led())
                        ->setLed($this->ledRepository->getByNumber(
                            $this->getModule($sequence->getModuleId() ?? 0),
                            $ledData['number']
                        ))
                        ->setRed($ledData['red'])
                        ->setGreen($ledData['green'])
                        ->setBlue($ledData['blue'])
                        ->setFadeIn($ledData['fadeIn'])
                        ->setBlink($ledData['blink']),
                ]);
            }

            $this->modelManager->save($image);
        }
    }

    /**
     * @throws SaveError
     * @throws SelectError
     * @throws ReflectionException
     * @throws JsonException
     */
    private function createAnimations(Type $type): void
    {
        foreach ($this->sequenceRepository->getByType($type, 1) as $sequence) {
            try {
                $this->animationRepository->getByName($this->getModule($sequence->getModuleId() ?? 0), $sequence->getName());

                continue;
            } catch (SelectError) {
                // do nothing
            }

            $animation = (new Animation())
                ->setModuleId($sequence->getModuleId() ?? 0)
                ->setName($sequence->getName())
            ;

            foreach ($sequence->getElements() as $element) {
                $leds = [];
                $time = 0;

                foreach (JsonUtility::decode($element->getData()) ?? [] as $sequenceLed) {
                    $time = $sequenceLed['time'];
                    $leds[] = (new Animation\Led())
                        ->setLed($this->ledRepository->getByNumber(
                            $this->getModule($sequence->getModuleId() ?? 0),
                            $sequenceLed['led']
                        ))
                        ->setRed($sequenceLed['red'])
                        ->setGreen($sequenceLed['green'])
                        ->setBlue($sequenceLed['blue'])
                        ->setFadeIn($sequenceLed['fadeIn'])
                        ->setBlink($sequenceLed['blink'])
                        ->setLength($sequenceLed['length'])
                    ;
                }

                $animation->addSteps([(new Animation\Step())
                    ->setTime($time)
                    ->setLeds($leds),
                ]);
            }

            $this->modelManager->save($animation);
        }
    }

    /**
     * @throws SelectError
     */
    private function getModule(int $moduleId): Module
    {
        return $this->modules[$moduleId] ?? $this->modules[$moduleId] = $this->moduleRepository->getById($moduleId);
    }
}
