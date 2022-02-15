<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Formatter;

use Exception;
use GibsonOS\Core\Attribute\GetSetting;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Model\Setting;
use GibsonOS\Core\Service\TwigService;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Formatter\Explain;
use GibsonOS\Module\Hc\Dto\Ir\Key;
use GibsonOS\Module\Hc\Model\Log;
use GibsonOS\Module\Hc\Model\Type;
use GibsonOS\Module\Hc\Repository\Attribute\ValueRepository;
use GibsonOS\Module\Hc\Repository\TypeRepository;
use GibsonOS\Module\Hc\Service\Slave\AbstractHcSlave;
use GibsonOS\Module\Hc\Service\Slave\IrService;
use GibsonOS\Module\Hc\Service\TransformService;
use JsonException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

class IrFormatter extends AbstractHcFormatter
{
    private array $irProtocols;

    private Type $type;

    private array $keyNames = [];

    /**
     * @throws SelectError
     * @throws JsonException
     */
    public function __construct(
        TransformService $transformService,
        TwigService $twigService,
        TypeRepository $typeRepository,
        #[GetSetting('irProtocols', 'hc')] Setting $irProtocols,
        private ValueRepository $valueRepository
    ) {
        parent::__construct($transformService, $twigService, $typeRepository);

        $this->irProtocols = JsonUtility::decode($irProtocols->getValue());
        $this->type = $this->typeRepository->getByHelperName('ir');
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
            AbstractHcSlave::COMMAND_DATA_CHANGED,
            AbstractHcSlave::COMMAND_STATUS,
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
            AbstractHcSlave::COMMAND_DATA_CHANGED,
            AbstractHcSlave::COMMAND_STATUS,
            IrService::COMMAND_SEND => $this->renderBlock(
                AbstractHcSlave::COMMAND_STATUS,
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
     * @return Key[]
     */
    public function getKeys(string $data): array
    {
        $keys = [];

        for ($i = 0; $i < strlen($data); $i += 5) {
            $key = new Key(
                $this->transformService->asciiToUnsignedInt($data, $i),
                ($this->transformService->asciiToUnsignedInt($data, $i + 1) << 8) |
                $this->transformService->asciiToUnsignedInt($data, $i + 2),
                ($this->transformService->asciiToUnsignedInt($data, $i + 3) << 8) |
                $this->transformService->asciiToUnsignedInt($data, $i + 4),
            );
            $key
                ->setName($this->getKeyName($key))
                ->setProtocolName($this->irProtocols[$key->getProtocol()] ?? null)
            ;

            $keys[] = $key;
        }

        return $keys;
    }

    public function getSubId(int $protocol, int $address, int $command): int
    {
        return $protocol << 32 | $address << 16 | $command;
    }

    public function getKeyBySubId(int $subId): Key
    {
        $key = new Key(
            $subId >> 32,
            ($subId >> 16) & 0xFFFF,
            $subId & 0xFFFF,
        );

        return $key;
    }

    private function getKeyName(Key $key): ?string
    {
        $subId = $this->getSubId($key->getProtocol(), $key->getAddress(), $key->getCommand());

        if (!array_key_exists($subId, $this->keyNames)) {
            $this->keyNames[$subId] = null;

            try {
                $keyNames = $this->valueRepository->getByTypeId(
                    $this->type->getId() ?? 0,
                    $subId,
                    type: IrService::ATTRIBUTE_TYPE_KEY,
                    key: IrService::KEY_ATTRIBUTE_NAME
                );

                if (count($keyNames) !== 0) {
                    $this->keyNames[$subId] = $keyNames[0]->getValue();
                }
            } catch (Exception) {
            }
        }

        return $this->keyNames[$subId];
    }

    protected function getTemplates(): array
    {
        return [
            IrService::COMMAND_SEND => 'ir/send',
            AbstractHcSlave::COMMAND_STATUS => 'ir/status',
            AbstractHcSlave::COMMAND_DATA_CHANGED => 'ir/status',
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
                    AbstractHcSlave::COMMAND_STATUS,
                    self::BLOCK_EXPLAIN,
                    ['protocol' => $this->irProtocols[$key->getProtocol()] ?? null]
                ) ?? ''
            ))->setColor(Explain::COLOR_GREEN);
            $explains[] = (new Explain(
                $i + 1,
                $i + 2,
                $this->renderBlock(
                    AbstractHcSlave::COMMAND_STATUS,
                    self::BLOCK_EXPLAIN,
                    ['address' => $key->getAddress()]
                ) ?? ''
            ))->setColor(Explain::COLOR_YELLOW);
            $explains[] = (new Explain(
                $i + 3,
                $i + 4,
                $this->renderBlock(
                    AbstractHcSlave::COMMAND_STATUS,
                    self::BLOCK_EXPLAIN,
                    ['command' => $key->getCommand()]
                ) ?? ''
            ))->setColor(Explain::COLOR_BLUE);
            $i += 5;
        }

        return $explains;
    }
}
