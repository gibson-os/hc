<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Command;

use GibsonOS\Core\Command\AbstractCommand;
use GibsonOS\Core\Manager\ModelManager;
use GibsonOS\Core\Mapper\ModelMapper;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use GibsonOS\Module\Hc\Repository\AttributeRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use Psr\Log\LoggerInterface;

/**
 * @description TEMP split attributes in tables
 */
class SplitAttributesInTablesCommand extends AbstractCommand
{
    public function __construct(
        LoggerInterface $logger,
        private TypeRepository $typeRepository,
        private AttributeRepository $attributeRepository,
        private ModelMapper $modelMapper,
        private ModelManager $modelManager
    ) {
        parent::__construct($logger);
    }

    protected function run(): int
    {
        $this->createLeds();

        return self::SUCCESS;
    }

    private function createLeds(): void
    {
        $ledAttributes = $this->attributeRepository->getByType(
            $this->typeRepository->getByHelperName('neopixel'),
            'led'
        );

        $leds = [];

        foreach ($ledAttributes as $ledAttribute) {
            $moduleId = $ledAttribute->getModuleId() ?? 0;
            $number = $ledAttribute->getSubId() ?? 0;

            if (!isset($leds[$moduleId])) {
                $leds[$moduleId] = [];
            }

            // @todo gucken ob es den Eintrag in der DB schon in der DB gibt. Wenn ja überspringen
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
}
