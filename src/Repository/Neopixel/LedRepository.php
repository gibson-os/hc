<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Core\Wrapper\RepositoryWrapper;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;
use JsonException;
use MDO\Dto\Query\Where;
use MDO\Enum\OrderDirection;
use MDO\Exception\ClientException;
use MDO\Exception\RecordException;
use MDO\Query\DeleteQuery;
use ReflectionException;

class LedRepository extends AbstractRepository
{
    public function __construct(
        RepositoryWrapper $repositoryWrapper,
        #[GetTableName(Led::class)]
        private readonly string $ledTableName,
    ) {
        parent::__construct($repositoryWrapper);
    }

    /**
     * @throws SelectError
     * @throws JsonException
     * @throws ClientException
     * @throws RecordException
     * @throws ReflectionException
     */
    public function getByNumber(Module $module, int $number): Led
    {
        return $this->fetchOne(
            '`module_id`=? AND `number`=?',
            [$module->getId(), $number],
            Led::class
        );
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Led[]
     */
    public function getByModule(Module $module): array
    {
        return $this->fetchAll(
            '`module_id`=?',
            [$module->getId()],
            Led::class,
            orderBy: ['`number`' => OrderDirection::ASC]
        );
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Led[]
     */
    public function getByChannel(Module $module, int $channel): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `channel`=?',
            [$module->getId(), $channel],
            Led::class,
            orderBy: ['`number`' => OrderDirection::ASC]
        );
    }

    /**
     * @param int[] $numbers
     *
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Led[]
     */
    public function getByNumbers(Module $module, array $numbers): array
    {
        return $this->fetchAll(
            sprintf(
                '`module_id`=? AND `number` IN (%s)',
                $this->getRepositoryWrapper()->getSelectService()->getParametersString($numbers),
            ),
            [$module->getId(), ...$numbers],
            Led::class,
            orderBy: ['`number`' => OrderDirection::ASC]
        );
    }

    public function deleteWithNumberBiggerAs(Module $module, int $number): bool
    {
        $deleteQuery = (new DeleteQuery($this->getTable($this->ledTableName)))
            ->addWhere(new Where('`module_id`=?', [$module->getId()]))
            ->addWhere(new Where('`number`>?', [$number]))
        ;

        try {
            $this->getRepositoryWrapper()->getClient()->execute($deleteQuery);
        } catch (ClientException) {
            return false;
        }

        return true;
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     * @throws SelectError
     */
    public function getById(int $moduleId, int $id): Led
    {
        return $this->fetchOne(
            '`module_id`=? AND `id`=?',
            [$moduleId, $id],
            Led::class
        );
    }

    /**
     * @throws ClientException
     * @throws JsonException
     * @throws RecordException
     * @throws ReflectionException
     *
     * @return Led[]
     */
    public function findByNumber(int $moduleId, string $name): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `number` REGEXP ?',
            [$moduleId, $this->getRegexString($name)],
            Led::class
        );
    }
}
