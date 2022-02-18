<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use Generator;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Mapper\ObjectMapper;
use GibsonOS\Core\Service\DateTimeService;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Type;
use mysqlDatabase;

/**
 * @method array|Generator getList()
 */
abstract class AbstractAttributeStore extends AbstractDatabaseStore
{
    protected ?int $moduleId = null;

    protected array $keys = [];

    abstract protected function getType(): string;

    abstract protected function getTypeName(): string;

    public function __construct(
        private DateTimeService $dateTimeService,
        protected ObjectMapper $objectMapper,
        #[GetTableName(Value::class)] protected string $valueTableName,
        #[GetTableName(Type::class)] protected string $typeTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($database);
    }

    protected function getModelClassName(): string
    {
        return Attribute::class;
    }

    protected function getCountField(): string
    {
        return '`' . $this->tableName . '`.`sub_id`';
    }

    protected function setWheres(): void
    {
        $tableName = $this->tableName;
        $this->addWhere('`' . $tableName . '`.`type`=?', [$this->getType()]);
        $this->addWhere('`' . $this->typeTableName . '`.`helper`=?', [$this->getTypeName()]);

        if ($this->moduleId !== null) {
            $this->addWhere('`' . $tableName . '`.`module_id`=?', [$this->moduleId]);
        }

        if (count($this->keys) > 0) {
            $this->addWhere(
                '`' . $tableName . '`.`key` IN (' . $this->table->getParametersString($this->keys) . ')',
                $this->keys
            );
        }
    }

    protected function getDefaultOrder(): string
    {
        $tableName = $this->tableName;

        return
            '`' . $tableName . '`.`type`, ' .
            '`' . $tableName . '`.`sub_id`, ' .
            '`' . $this->valueTableName . '`.`order`'
        ;
    }

    protected function initTable(): void
    {
        parent::initTable();

        $valueTableName = $this->valueTableName;
        $tableName = $this->tableName;
        $typeTableName = $this->typeTableName;
        $this->table
            ->appendJoinLeft(
                '`' . $valueTableName . '`',
                '`' . $tableName . '`.`id`=`' . $valueTableName . '`.`attribute_id`'
            )
            ->appendJoinLeft(
                '`' . $typeTableName . '`',
                '`' . $tableName . '`.`type_id`=`' . $typeTableName . '`.`id`'
            )
        ;
    }

    /**
     * @throws SelectError
     *
     * @return Attribute[]
     */
    protected function getModels(): iterable
    {
        if ($this->table->selectPrepared(false, '`' . $this->tableName . '`.*, `' . $this->valueTableName . '`.*') === false) {
            $exception = new SelectError($this->table->connection->error());
            $exception->setTable($this->table);

            throw $exception;
        }

        $attributes = [];

        while ($row = $this->table->connection->fetchObject()) {
            if (!isset($attributes[$row->id])) {
                $attributes[$row->id] = (new Attribute())
                    ->setId($row->id)
                    ->setTypeId($row->type_id)
                    ->setModuleId($row->module_id)
                    ->setSubId($row->sub_id)
                    ->setKey($row->key)
                    ->setType($row->type)
                    ->setAdded($this->dateTimeService->get($row->added))
                    ->setValues([])
                ;
            }

            $attributes[$row->id]->addValues([
                (new Value())
                    ->setAttribute($attributes[$row->id])
                    ->setValue($row->value)
                    ->setOrder($row->order),
            ]);
        }

        return array_values($attributes);
    }

    public function setModuleId(?int $moduleId): AbstractAttributeStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function setKeys(array $keys): AbstractAttributeStore
    {
        $this->keys = $keys;

        return $this;
    }
}
