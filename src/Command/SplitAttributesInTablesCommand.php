<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Exception\FactoryError;
use GibsonOS\Core\Exception\MapperException;
use GibsonOS\Core\Exception\Model\SaveError;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Mapper\ModelMapper;
use GibsonOS\Core\Model\Event\Element;
use GibsonOS\Core\Model\Event\Trigger;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Ir\Protocol;
use GibsonOS\Module\Hc\Event\IrEvent;
use GibsonOS\Module\Hc\Event\NeopixelEvent;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Ir\Remote;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Animation;
use GibsonOS\Module\Hc\Model\Neopixel\Image;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\Io\PortRepository;
use GibsonOS\Module\Hc\Repository\Ir\KeyRepository;
use GibsonOS\Module\Hc\Repository\Ir\RemoteRepository;
use GibsonOS\Module\Hc\Repository\ModuleRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\AnimationRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\ImageRepository;
use GibsonOS\Module\Hc\Repository\Neopixel\LedRepository;
use GibsonOS\Module\Hc\Repository\SequenceRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use JsonException;
use mysqlDatabase;
use mysqlTable;
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
        private readonly KeyRepository $keyRepository,
        private readonly RemoteRepository $remoteRepository,
        private readonly PortRepository $portRepository,
        private readonly mysqlDatabase $mysqlDatabase,
        #[GetTableName(Element::class)] private readonly string $eventElementTableName,
        #[GetTableName(Trigger::class)] private readonly string $eventTriggerTableName,
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
        $this->splitIo();
        $this->splitIr();

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
        $this->fixNeopixelEvents();
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function splitIo(): void
    {
        $type = $this->typeRepository->getByHelperName('io');
        $this->createPorts($type);
        $this->createDirectConnects($type);
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function splitIr(): void
    {
        $type = $this->typeRepository->getByHelperName('ir');
        $this->createIrKeys($type);
        $this->createRemotes($type);
        $this->fixIrEvents();
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

            foreach (JsonUtility::decode($element->getData()) ?? [] as $number => $ledData) {
                $image->addLeds([
                    (new Image\Led())
                        ->setLed($this->ledRepository->getByNumber(
                            $this->getModule($sequence->getModuleId() ?? 0),
                            $number
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
                foreach (JsonUtility::decode($element->getData()) ?? [] as $sequenceLed) {
                    $time = $sequenceLed['time'];
                    $animation->addLeds([(new Animation\Led())
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
                        ->setTime($time),
                    ]);
                }
            }

            $this->modelManager->save($animation);
        }
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function createPorts(Type $type): void
    {
        $ports = [];

        foreach ($this->attributeRepository->getByType($type, 'port') as $portAttribute) {
            $moduleId = $portAttribute->getModuleId() ?? 0;
            $number = $portAttribute->getSubId() ?? 0;

            if (!isset($ports[$moduleId])) {
                $ports[$moduleId] = [];
            }

            if (!isset($ports[$moduleId][$number])) {
                $ports[$moduleId][$number] = [
                    'module' => $portAttribute->getModule(),
                    'number' => $number,
                ];
            }

            $key = $portAttribute->getKey();

            if ($key === 'valueName') {
                continue;
            }

            $ports[$moduleId][$number][$key] = match ($key) {
                'valueNames' => array_map(
                    fn (Value $value): string => $value->getValue(),
                    $portAttribute->getValues(),
                ),
                'name' => $portAttribute->getValues()[0]->getValue(),
                default => (int) $portAttribute->getValues()[0]->getValue()
            };
        }

        foreach ($ports as $modulePorts) {
            foreach ($modulePorts as $port) {
                $this->modelManager->save($this->modelMapper->mapToObject(Port::class, $port));
            }
        }
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function createDirectConnects(Type $type): void
    {
        $directConnects = [];

        foreach ($this->attributeRepository->getByType($type, 'directConnect') as $directConnect) {
            $inputPortNumber = $directConnect->getSubId() ?? 0;
            $key = $directConnect->getKey();
            $module = $this->getModule($directConnect->getModuleId() ?? 0);
            $moduleId = $module->getId() ?? 0;
            $inputPort = $this->portRepository->getByNumber($module, $inputPortNumber);

            if (!isset($directConnects[$moduleId])) {
                $directConnects[$moduleId] = [];
            }

            if (!isset($directConnects[$moduleId][$inputPortNumber])) {
                $directConnects[$moduleId][$inputPortNumber] = [];
            }

            foreach ($directConnect->getValues() as $i => $value) {
                if (!isset($directConnects[$moduleId][$inputPortNumber][$i])) {
                    $directConnects[$moduleId][$inputPortNumber][$i] = [
                        'module' => $module,
                        'inputPort' => $inputPort,
                        'order' => count($directConnects[$moduleId][$inputPortNumber]),
                    ];
                }

                $value = (int) $value->getValue();

                if ($key === 'outputPort') {
                    $value = $this->portRepository->getByNumber($module, $value);
                } elseif ($key === 'inputPortValue') {
                    $key = 'inputValue';
                    $value = (bool) $value;
                } elseif ($key === 'value') {
                    $value = (bool) $value;
                }

                $directConnects[$moduleId][$inputPortNumber][$i][$key] = $value;
            }
        }

        foreach ($directConnects as $modulePortDirectConnects) {
            foreach ($modulePortDirectConnects as $inputPortDirectConnects) {
                foreach ($inputPortDirectConnects as $directConnect) {
                    $this->modelManager->save($this->modelMapper->mapToObject(DirectConnect::class, $directConnect));
                }
            }
        }
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function createIrKeys(Type $type): void
    {
        foreach ($this->attributeRepository->getByType($type, 'key') as $keyAttribute) {
            $subId = $keyAttribute->getSubId() ?? 0;
            $keyAttribute->getValues();

            $key = (new Key())
                ->setName($keyAttribute->getValues()[0]->getValue())
                ->setProtocol(Protocol::from($subId >> 32))
                ->setAddress(($subId >> 16) & 0xFFFF)
                ->setCommand($subId & 0xFFFF)
            ;
            $this->modelManager->save($key);
        }
    }

    /**
     * @throws FactoryError
     * @throws JsonException
     * @throws MapperException
     * @throws ReflectionException
     * @throws SaveError
     * @throws SelectError
     */
    private function createRemotes(Type $type): void
    {
        $remotes = [];

        foreach ($this->attributeRepository->getByType($type, 'remote') as $remoteAttribute) {
            $subId = $remoteAttribute->getSubId() ?? 0;
            $key = $remoteAttribute->getKey();
            $values = $remoteAttribute->getValues();

            if (!isset($remotes[$subId])) {
                $remotes[$subId] = [];
            }

            if ($key !== 'keys') {
                $remotes[$subId][$key] = $values[0]->getValue();

                continue;
            }

            $buttons = [];

            foreach ($values as $key) {
                $button = JsonUtility::decode($key->getValue());

                foreach ($button['keys'] as $i => &$buttonKey) {
                    $buttonKey = (new Remote\Key())
                        ->setOrder($i)
                        ->setKey(
                            $this->keyRepository->getByProtocolAddressAndCommand(
                                Protocol::from($buttonKey >> 32),
                                ($buttonKey >> 16) & 0xFFFF,
                                $buttonKey & 0xFFFF,
                            )
                        )
                    ;
                }

                $buttons[] = $button;
            }

            $remotes[$subId]['buttons'] = $buttons;
        }

        foreach ($remotes as $remote) {
            try {
                $name = $remote['name'];

                if (is_string($name)) {
                    $this->remoteRepository->getByName($name);
                }
            } catch (SelectError) {
                $this->modelManager->save($this->modelMapper->mapToObject(Remote::class, $remote));
            }
        }
    }

    private function fixNeopixelEvents(): void
    {
        $eventElementTable = (new mysqlTable($this->mysqlDatabase, $this->eventElementTableName))
            ->setWhere('`class`=? AND `method`=?')
            ->setWhereParameters([NeopixelEvent::class, 'sendImage'])
        ;

        if ($eventElementTable->selectPrepared()) {
            do {
                /** @psalm-suppress UndefinedPropertyFetch */
                $parameters = JsonUtility::decode($eventElementTable->parameters->getValue());
                $sequenceParameter = $parameters['sequence'] ?? null;

                if ($sequenceParameter === null) {
                    continue;
                }

                try {
                    $sequence = $this->sequenceRepository->getById($sequenceParameter);
                    $image = $this->imageRepository->getByName(
                        $this->getModule($parameters['module'] ?? 0),
                        $sequence->getName()
                    );
                } catch (SelectError) {
                    continue;
                }

                unset($parameters['sequence']);
                $parameters['image'] = $image->getId();
                /** @psalm-suppress UndefinedPropertyFetch */
                $eventElementTable->parameters->setValue(JsonUtility::encode($parameters));
                $eventElementTable->save();
            } while ($eventElementTable->next());
        }
    }

    private function fixIrEvents(): void
    {
        $eventElementTable = (new mysqlTable($this->mysqlDatabase, $this->eventElementTableName))
            ->setWhere('`class`=? AND `method`=?')
            ->setWhereParameters([IrEvent::class, 'sendKey'])
        ;

        if ($eventElementTable->selectPrepared()) {
            do {
                /** @psalm-suppress UndefinedPropertyFetch */
                $parameters = JsonUtility::decode($eventElementTable->parameters->getValue());
                $keyParameter = $parameters['key'];

                try {
                    $this->keyRepository->getById($keyParameter);

                    continue;
                } catch (SelectError) {
                    // do nothing
                }

                try {
                    $key = $this->keyRepository->getByProtocolAddressAndCommand(
                        Protocol::from($keyParameter >> 32),
                        ($keyParameter >> 16) & 0xFFFF,
                        $keyParameter & 0xFFFF,
                    );
                } catch (SelectError) {
                    continue;
                }

                $parameters['key'] = $key->getId();
                /** @psalm-suppress UndefinedPropertyFetch */
                $eventElementTable->parameters->setValue(JsonUtility::encode($parameters));
                $eventElementTable->save();
            } while ($eventElementTable->next());
        }

        $eventTriggerTable = (new mysqlTable($this->mysqlDatabase, $this->eventTriggerTableName))
            ->setWhere('`class`=? AND `trigger`=?')
            ->setWhereParameters([IrEvent::class, 'afterReadIr'])
        ;

        if ($eventTriggerTable->selectPrepared()) {
            do {
                /** @psalm-suppress UndefinedPropertyFetch */
                $parameters = JsonUtility::decode($eventTriggerTable->parameters->getValue());
                $keyParameter = $parameters['key']['value'];

                try {
                    $this->keyRepository->getById($keyParameter);

                    continue;
                } catch (SelectError) {
                    // do nothing
                }

                try {
                    $key = $this->keyRepository->getByProtocolAddressAndCommand(
                        Protocol::from($keyParameter >> 32),
                        ($keyParameter >> 16) & 0xFFFF,
                        $keyParameter & 0xFFFF,
                    );
                } catch (SelectError) {
                    continue;
                }

                $parameters['key']['value'] = $key->getId();
                $eventTriggerTable->parameters->setValue(JsonUtility::encode($parameters));
                $eventTriggerTable->save();
            } while ($eventTriggerTable->next());
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
