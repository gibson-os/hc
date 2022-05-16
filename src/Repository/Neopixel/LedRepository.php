<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Repository\Neopixel;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\DeleteError;
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
        return $this->fetchOne(
            '`module_id`=? AND `number`=?',
            [$module->getId(), $number],
            Led::class
        );
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
            '`module_id`=? AND `number` IN (' . $this->getTable($this->ledTableName)->getParametersString($numbers) . ')',
            [$module->getId(), ...$numbers],
            Led::class,
            orderBy: '`number`'
        );
    }

    public function deleteWithNumberBiggerAs(Module $module, int $number): void
    {
        $table = self::getTable($this->ledTableName);
        $table
            ->setWhere('`module_id`=? AND `number`>?')
            ->setWhereParameters([$module->getId(), $number])
        ;

        if (!$table->deletePrepared()) {
            $exception = new DeleteError(sprintf(
                'LEDs with number bigger as %d could not be deleted on module "%s"',
                $number,
                $module->getName()
            ));
            $exception->setTable($table);

            throw $exception;
        }
    }
}
