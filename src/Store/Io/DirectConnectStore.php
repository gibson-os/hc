<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Io;

use Generator;
use GibsonOS\Core\Attribute\GetTableName;
use GibsonOS\Core\Exception\Repository\SelectError;
use GibsonOS\Core\Store\AbstractDatabaseStore;
use GibsonOS\Core\Utility\JsonUtility;
use GibsonOS\Module\Hc\Dto\Io\Direction;
use GibsonOS\Module\Hc\Model\Io\DirectConnect;
use GibsonOS\Module\Hc\Model\Io\Port;
use GibsonOS\Module\Hc\Model\Module;
use mysqlDatabase;

/**
 * @extends AbstractDatabaseStore<Port>
 *
 * @method Generator<Port> getList()
 */
class DirectConnectStore extends AbstractDatabaseStore
{
    protected Module $module;

    public function __construct(
        #[GetTableName(DirectConnect::class)]
        private readonly string $directConnectTableName,
        #[GetTableName(Port::class)]
        private readonly string $portTableName,
        mysqlDatabase $database = null,
    ) {
        parent::__construct($database);
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    protected function getModels(): iterable
    {
        $this->table->appendJoinLeft(
            '`' . $this->directConnectTableName . '`',
            '`' . $this->portTableName . '`.`id`=`' . $this->directConnectTableName . '`.`input_port_id`'
        );
        $this->table->appendJoinLeft(
            '`' . $this->portTableName . '` `output_port`',
            '`output_port`.`id`=`' . $this->directConnectTableName . '`.`output_port_id`'
        );
        $this->table->setSelectString(
            '`' . $this->directConnectTableName . '`.`id` `direct_connect_id`, ' .
            '`' . $this->directConnectTableName . '`.`input_value`, ' .
            '`' . $this->directConnectTableName . '`.`value`, ' .
            '`' . $this->directConnectTableName . '`.`pwm`, ' .
            '`' . $this->directConnectTableName . '`.`blink`, ' .
            '`' . $this->directConnectTableName . '`.`fade_in`, ' .
            '`' . $this->portTableName . '`.`id` `input_id`, ' .
            '`' . $this->portTableName . '`.`direction` `input_port_direction`, ' .
            '`' . $this->portTableName . '`.`name` `input_port_name`, ' .
            '`' . $this->portTableName . '`.`number` `input_port_number`, ' .
            '`' . $this->portTableName . '`.`value` `input_port_value`, ' .
            '`' . $this->portTableName . '`.`value_names` `input_port_value_names`, ' .
            '`' . $this->portTableName . '`.`delay` `input_port_delay`, ' .
            '`' . $this->portTableName . '`.`pull_up` `input_port_pull_up`, ' .
            '`' . $this->portTableName . '`.`pwm` `input_port_pwm`, ' .
            '`' . $this->portTableName . '`.`blink` `input_port_blink`, ' .
            '`' . $this->portTableName . '`.`fade_in` `input_port_fade_in`, ' .
            '`' . $this->directConnectTableName . '`.`output_port_id`, ' .
            '`output_port`.`direction` `output_port_direction`, ' .
            '`output_port`.`name` `output_port_name`, ' .
            '`output_port`.`number` `output_port_number`, ' .
            '`output_port`.`value` `output_port_value`, ' .
            '`output_port`.`value_names` `output_port_value_names`, ' .
            '`output_port`.`delay` `output_port_delay`, ' .
            '`output_port`.`pull_up` `output_port_pull_up`, ' .
            '`output_port`.`pwm` `output_port_pwm`, ' .
            '`output_port`.`blink` `output_port_blink`, ' .
            '`output_port`.`fade_in` `output_port_fade_in`'
        );

        if ($this->table->selectPrepared() === false) {
            $exception = new SelectError($this->table->connection->error());
            $exception->setTable($this->table);

            throw $exception;
        }

        foreach ($this->table->getRecords() as $record) {
            $directConnect = (new DirectConnect())
                ->setInputValue((bool) ((int) $record['input_value']))
                ->setValue((bool) ((int) $record['value']))
                ->setPwm((int) $record['pwm'])
                ->setBlink((int) $record['blink'])
                ->setFadeIn((int) $record['fade_in'])
                ->setInputPort(
                    (new Port())
                        ->setId((int) $record['input_id'])
                        ->setDirection(Direction::from((int) $record['input_port_direction']))
                        ->setName($record['input_port_name'])
                        ->setNumber((int) $record['input_port_number'])
                        ->setValue((bool) ((int) $record['input_port_value']))
                        ->setValueNames(JsonUtility::decode($record['input_port_value_names']))
                        ->setDelay((int) $record['input_port_delay'])
                        ->setPullUp((bool) ((int) $record['input_port_pull_up']))
                        ->setPwm((int) $record['input_port_pwm'])
                        ->setBlink((int) $record['input_port_blink'])
                        ->setFadeIn((int) $record['input_port_fade_in'])
                );

            if (isset($record['direct_connect_id'])) {
                $directConnect
                    ->setId((int) $record['direct_connect_id'])
                    ->setOutputPort(
                        (new Port())
                            ->setId((int) $record['output_port_id'])
                            ->setDirection(Direction::from((int) $record['output_port_direction']))
                            ->setName($record['output_port_name'])
                            ->setNumber((int) $record['output_port_number'])
                            ->setValue((bool) ((int) $record['output_port_value']))
                            ->setValueNames(JsonUtility::decode($record['output_port_value_names']))
                            ->setDelay((int) $record['output_port_delay'])
                            ->setPullUp((bool) ((int) $record['output_port_pull_up']))
                            ->setPwm((int) $record['output_port_pwm'])
                            ->setBlink((int) $record['output_port_blink'])
                            ->setFadeIn((int) $record['output_port_fade_in'])
                    )
                ;
            }

            yield $directConnect;
        }
    }

    protected function getModelClassName(): string
    {
        return Port::class;
    }

    protected function setWheres(): void
    {
        $this->addWhere('`' . $this->portTableName . '`.`module_id`=?', [$this->module->getId()]);
    }

    protected function getDefaultOrder(): string
    {
        return '`' . $this->portTableName . '`.`number`, `' . $this->directConnectTableName . '`.`order`';
    }
}
