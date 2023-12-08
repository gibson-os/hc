<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Core\Wrapper\ModelWrapper;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Enum\Ir\Protocol;
use GibsonOS\Module\Hc\Model\Ir\Key;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Repository\Ir\KeyRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Module\AbstractHcModule;
use GibsonOS\Module\Hc\Service\Module\IrService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use ReflectionException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class IrFormatter extends AbstractHcFormatter
{
    public function __construct(
        TransformService $transformService,
        TwigService $twigService,
        TypeRepository $typeRepository,
        ModelWrapper $modelWrapper,
        private readonly KeyRepository $keyRepository,
    ) {
        parent::__construct($transformService, $twigService, $typeRepository, $modelWrapper);
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     *
     * @return Explain[]|null
     */
    public function explain(Log $log): ?array
    {
        $command = $log->getCommand();

        return match ($command) {
            AbstractHcModule::COMMAND_DATA_CHANGED,
            AbstractHcModule::COMMAND_STATUS,
            IrService::COMMAND_SEND => $this->explainCommands($log->getRawData()),
            default => parent::explain($log),
        };
    }

    /**
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Throwable
     */
    public function render(Log $log): ?string
    {
        return match ($log->getCommand()) {
            AbstractHcModule::COMMAND_DATA_CHANGED,
            AbstractHcModule::COMMAND_STATUS,
            IrService::COMMAND_SEND => $this->renderBlock(
                AbstractHcModule::COMMAND_STATUS,
                AbstractHcFormatter::BLOCK_RENDER,
                [
                    'irProtocols' => $this->irProtocols,
                    'keys' => $this->getKeys($log->getRawData()),
                ]
            ) ?? parent::render($log),
            default => parent::render($log)
        };
    }

    /**
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Key[]
     */
    public function getKeys(string $data): array
    {
        $keys = [];

        for ($i = 0; $i < strlen($data); $i += 5) {
            $protocol = Protocol::from($this->transformService->asciiToUnsignedInt($data, $i));
            $address =
                ($this->transformService->asciiToUnsignedInt($data, $i + 1) << 8) |
                $this->transformService->asciiToUnsignedInt($data, $i + 2)
            ;
            $command =
                ($this->transformService->asciiToUnsignedInt($data, $i + 3) << 8) |
                $this->transformService->asciiToUnsignedInt($data, $i + 4)
            ;

            try {
                $key = $this->keyRepository->getByProtocolAddressAndCommand($protocol, $address, $command);
            } catch (SelectError) {
                $key = (new Key($this->modelWrapper))
                    ->setProtocol($protocol)
                    ->setCommand($command)
                    ->setAddress($address)
                ;
            }

            $keys[] = $key;
        }

        return $keys;
    }

    protected function getTemplates(): array
    {
        return [
            IrService::COMMAND_SEND => 'ir/send',
            AbstractHcModule::COMMAND_STATUS => 'ir/status',
            AbstractHcModule::COMMAND_DATA_CHANGED => 'ir/status',
        ] + parent::getTemplates();
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws SyntaxError
     *
     * @return Explain[]
     */
    private function explainCommands(string $data): array
    {
        $explains = [];
        $i = 0;

        foreach ($this->getKeys($data) as $key) {
            $explains[] = (new Explain(
                $i,
                $i,
                $this->renderBlock(
                    AbstractHcModule::COMMAND_STATUS,
                    self::BLOCK_EXPLAIN,
                    ['protocol' => $key->getProtocol()->getName()]
                ) ?? ''
            ))->setColor(Explain::COLOR_GREEN);
            $explains[] = (new Explain(
                $i + 1,
                $i + 2,
                $this->renderBlock(
                    AbstractHcModule::COMMAND_STATUS,
                    self::BLOCK_EXPLAIN,
                    ['address' => $key->getAddress()]
                ) ?? ''
            ))->setColor(Explain::COLOR_YELLOW);
            $explains[] = (new Explain(
                $i + 3,
                $i + 4,
                $this->renderBlock(
                    AbstractHcModule::COMMAND_STATUS,
                    self::BLOCK_EXPLAIN,
                    ['command' => $key->getCommand()]
                ) ?? ''
            ))->setColor(Explain::COLOR_BLUE);
            $i += 5;
        }

        return $explains;
    }
}
