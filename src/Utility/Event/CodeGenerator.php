<?php
namespace GibsonOS\Core\Utility\Event;

use GibsonOS\Module\Hc\Model\Event\Element as ElementModel;

class CodeGenerator
{
    const COMMAND_IF = 'if';
    const COMMAND_IF_NOT = 'if_not';
    const COMMAND_ELSE = 'else';
    const COMMAND_ELSE_IF = 'else_if';
    const COMMAND_ELSE_IF_NOT = 'else_if_not';
    const COMMAND_WHILE = 'while';
    const COMMAND_WHILE_NOT = 'while_not';
    const COMMAND_DO_WHILE = 'do_while';
    const COMMAND_DO_WHILE_NOT = 'do_while_not';

    /**
     * @var int
     */
    private static $parentId;
    /**
     * @var ElementModel[]
     */
    private static $parents;

    /**
     * @param ElementModel[] $elements
     * @return null|string
     */
    public static function generateByElements($elements)
    {
        $code = null;

        foreach ($elements as $element) {
            $code .= self::generateCommandEnd($element);
            $code .= self::generateCommandStart($element);
        }

        return $code;
    }

    /**
     * @param ElementModel $element
     * @return string
     */
    private static function generateCommandStart(ElementModel $element)
    {
        $command = '$this->runFunction(\'' . serialize($element) . '\')';

        if ($element->getCommand() !== null) {
            self::$parentId = $element->getId();
            self::$parents[self::$parentId] = $element;
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
     * @return null|string
     */
    private static function generateCommandEnd(ElementModel $element)
    {
        if (self::$parentId === $element->getParentId()) {
            return null;
        }

        $parent = self::$parents[self::$parentId];
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

        self::$parentId = $element->getParentId();

        return null;
    }
}