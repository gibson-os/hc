<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Service\Event;

use GibsonOS\Core\Service\AbstractService;
use GibsonOS\Module\Hc\Model\Event\Element;

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
     * @var int|null
     */
    private $parentId;

    /**
     * @var Element[]
     */
    private $parents = [];

    /**
     * @param Element[] $elements
     */
    public function generateByElements(array $elements): string
    {
        $this->parentId = null;
        $this->parents = [];
        $code = '';

        foreach ($elements as $element) {
            $code .= $this->generateCommandEnd($element);
            $code .= $this->generateCommandStart($element);
        }

        return $code;
    }

    private function generateCommandStart(Element $element): string
    {
        $command = '$this->runFunction(unserialize(\'' . str_replace("'", "\\'", serialize($element)) . '\'))';

        if (!empty($element->getCommand())) {
            $this->parentId = (int) $element->getId();
            $this->parents[$this->parentId] = $element;
        }

        switch ($element->getCommand()) {
            case self::COMMAND_IF:
                return 'if (' . $command . ' === ' . $element->getValue() . ') {';
            case self::COMMAND_IF_NOT:
                return 'if (' . $command . ' !== ' . $element->getValue() . ') {';
            case self::COMMAND_ELSE:
                return '} else {';
            case self::COMMAND_ELSE_IF:
                return '} else if (' . $command . ' === ' . $element->getValue() . ') {';
            case self::COMMAND_ELSE_IF_NOT:
                return '} else if (' . $command . ' !== ' . $element->getValue() . ') {';
            case self::COMMAND_WHILE:
                return 'while (' . $command . ' === ' . $element->getValue() . ') {';
            case self::COMMAND_WHILE_NOT:
                return 'while (' . $command . ' !== ' . $element->getValue() . ') {';
            case self::COMMAND_DO_WHILE:
            case self::COMMAND_DO_WHILE_NOT:
                return '{';
        }

        return $command . ';';
    }

    private function generateCommandEnd(Element $element): string
    {
        if ($this->parentId === $element->getParentId()) {
            return '';
        }

        if ($this->parentId !== null) {
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
        }

        $this->parentId = $element->getParentId();

        return '';
    }
}
