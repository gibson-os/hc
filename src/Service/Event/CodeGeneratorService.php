<?php
declare(strict_types=1);

namespace GibsonOS\Core\Utility\Event;

use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;

class CodeGeneratorService extends AbstractService
{
    private const COMMAND_IF = 'if';

    private const COMMAND_IF_NOT = 'if_not';

    private const COMMAND_ELSE = 'else';

    private const COMMAND_ELSE_IF = 'else_if';

    private const COMMAND_ELSE_IF_NOT = 'else_if_not';

    private const COMMAND_WHILE = 'while';

    private const COMMAND_WHILE_NOT = 'while_not';

    private const COMMAND_DO_WHILE = 'do_while';

    private const COMMAND_DO_WHILE_NOT = 'do_while_not';

    /**
     * @var int
     */
    private $parentId = 0;

    /**
     * @var ElementModel[]
     */
    private $parents = [];

    /**
     * @param ElementModel[] $elements
     *
     * @return string
     */
    public function generateByElements(array $elements): string
    {
        $this->parentId = 0;
        $this->parents = [];
        $code = '';

        foreach ($elements as $element) {
            $code .= self::generateCommandEnd($element);
            $code .= self::generateCommandStart($element);
        }

        return $code;
    }

    /**
     * @param ElementModel $element
     *
     * @return string
     */
    private function generateCommandStart(ElementModel $element): string
    {
        $command = '$this->runFunction(\'' . serialize($element) . '\')';

        if ($element->getCommand() !== null) {
            $this->parentId = $element->getId();
            $this->parents[$this->parentId] = $element;
        }

        switch ($element->getCommand()) {
            case self::COMMAND_IF:
                return 'if (' . $command . ' == ' . $element->getValue() . ') {';
            case self::COMMAND_IF_NOT:
                return 'if (' . $command . ' != ' . $element->getValue() . ') {';
            case self::COMMAND_ELSE:
                return '} else {';
            case self::COMMAND_ELSE_IF:
                return '} else if (' . $command . ' == ' . $element->getValue() . ') {';
            case self::COMMAND_ELSE_IF_NOT:
                return '} else if (' . $command . ' != ' . $element->getValue() . ') {';
            case self::COMMAND_WHILE:
                return 'while (' . $command . ' == ' . $element->getValue() . ') {';
            case self::COMMAND_WHILE_NOT:
                return 'while (' . $command . ' != ' . $element->getValue() . ') {';
            case self::COMMAND_DO_WHILE:
            case self::COMMAND_DO_WHILE_NOT:
                return '{';
        }

        return $command . ';';
    }

    /**
     * @param ElementModel $element
     *
     * @return string
     */
    private function generateCommandEnd(ElementModel $element): string
    {
        if ($this->parentId === $element->getParentId()) {
            return '';
        }

        $parent = $this->parents[$this->parentId];
        $command = '$this->runFunction(\'' . serialize($parent) . '\')';

        switch ($parent->getCommand()) {
            case self::COMMAND_IF:
            case self::COMMAND_IF_NOT:
            case self::COMMAND_ELSE:
            case self::COMMAND_ELSE_IF:
            case self::COMMAND_ELSE_IF_NOT:
            case self::COMMAND_WHILE:
            case self::COMMAND_WHILE_NOT:
                return '}';
            case self::COMMAND_DO_WHILE:
                return '} while (' . $command . ' == ' . $element->getValue() . ');';
            case self::COMMAND_DO_WHILE_NOT:
                return '} while (' . $command . ' != ' . $element->getValue() . ');';
        }

        $this->parentId = $element->getParentId();

        return '';
    }
}
