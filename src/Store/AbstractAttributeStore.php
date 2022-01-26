<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store;

use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Service\AttributeService;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Module\Hc\Model\Attribute;
use GibsonOS\Module\Hc\Model\Attribute\Value;
use GibsonOS\Module\Hc\Model\Type;
use mysqlDatabase;

abstract class AbstractAttributeStore extends AbstractDatabaseStore
{
    protected ?int $moduleId = null;

    abstract protected function getType(): string;

    abstract protected function getTypeName(): string;

    public function __construct(
        AttributeService $attributeService,
        #[GetTableName(Value::class)] protected string $valueTableName,
        #[GetTableName(Type::class)] protected string $typeTableName,
        mysqlDatabase $database = null
    ) {
        parent::__construct($attributeService, $database);
    }

    protected function getModelClassName(): string
    {
        return Attribute::class;
    }

    protected function setWheres(): void
    {
        $tableName = $this->tableName;
        $this->addWhere('`' . $tableName . '`.`type`=?', [$this->getType()]);
        $this->addWhere('`' . $this->typeTableName . '`.`helper`=?', [$this->getTypeName()]);

        if ($this->moduleId !== null) {
            $this->addWhere('`' . $tableName . '`.`module_id`=?', [$this->moduleId]);
        }
    }

    protected function initTable(): void
    {
        parent::initTable();

        $this->table
            ->appendJoinLeft(
                '`' . $this->valueTableName . '`',
                '`' . $this->tableName . '`.`id`=`' . $this->valueTableName . '`.`attribute_id`'
            )
            ->appendJoinLeft(
                '`' . $this->typeTableName . '`',
                '`' . $this->tableName . '`.`type_id`=`' . $this->typeTableName . '`.`id`'
            );
    }

    public function setModuleId(?int $moduleId): AbstractAttributeStore
    {
        $this->moduleId = $moduleId;

        return $this;
    }
}
