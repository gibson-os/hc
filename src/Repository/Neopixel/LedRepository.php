<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Repository\AbstractRepository;
use GibsonOS\Module\Hc\Model\Module;
use GibsonOS\Module\Hc\Model\Neopixel\Led;

class LedRepository extends AbstractRepository
{
    public function __construct(#[GetTableName(Led::class)] private string $ledTableName)
    {
    }

    /**
     * @throws SelectError
     */
    public function getByNumber(Module $module, int $number): Led
    {
        return $this->fetchOne('`id`=?', [$number], Led::class);
    }

    /**
     * @throws SelectError
     *
     * @return Led[]
     */
    public function getByModule(Module $module): array
    {
        return $this->fetchAll(
            '`module_id`=?',
            [$module->getId()],
            Led::class,
            orderBy: '`number`'
        );
    }

    /**
     * @throws SelectError
     *
     * @return Led[]
     */
    public function getByChannel(Module $module, int $channel): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `channel`=?',
            [$module->getId(), $channel],
            Led::class,
            orderBy: '`number`'
        );
    }

    /**
     * @param int[] $numbers
     *
     * @throws SelectError
     *
     * @return Led[]
     */
    public function getByNumbers(Module $module, array $numbers): array
    {
        return $this->fetchAll(
            '`module_id`=? AND `numbers` IN (' . $this->getTable($this->ledTableName)->getParametersString($numbers) . ')',
            [$module->getId(), ...$numbers],
            Led::class,
            orderBy: '`number`'
        );
    }
}
