<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Store\Event;

use GibsonOS\Core\Store\AbstractStore;
use GibsonOS\Module\Hc\Dto\Event\Describer\Parameter\AbstractParameter;
use GibsonOS\Module\Hc\Service\Event\Describer\DescriberInterface;

class MethodStore extends AbstractStore
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var array[]
     */
    private $list = [];

    /**
     * Method constructor.
     *
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * @return array[]
     */
    public function getList(): array
    {
        $this->generateList();

        return $this->list;
    }

    public function getCount(): int
    {
        return count($this->getList());
    }

    private function generateList(): void
    {
        if (count($this->list) !== 0) {
            return;
        }

        $classNameWithNamespace = '\\GibsonOS\\Module\\Hc\\Service\\Event\\Describer\\' . $this->className;
        $class = new $classNameWithNamespace();
        $methods = [];

        if (!$class instanceof DescriberInterface) {
            $this->list = $methods;

            return;
        }

        foreach ($class->getMethods() as $name => $method) {
            $methods[$method->getTitle()] = [
                'method' => $name,
                'title' => $method->getTitle(),
                'parameters' => $this->transformParameters($method->getParameters()),
                'returnType' => $this->transformReturnTypes($method->getReturnTypes()),
            ];
        }

        ksort($methods);

        $this->list = array_values($methods);
    }

    /**
     * @param AbstractParameter[] $parameters
     */
    private function transformParameters(array $parameters): array
    {
        $parametersArray = [];

        foreach ($parameters as $name => $parameter) {
            $parametersArray[$name] = [
                'title' => $parameter->getTitle(),
                'type' => $parameter->getType(),
                'config' => $parameter->getConfig(),
            ];
        }

        return $parametersArray;
    }

    /**
     * @param AbstractParameter[]|AbstractParameter[][] $returnTypes
     */
    private function transformReturnTypes(array $returnTypes): array
    {
        $returnTypesArray = [];

        foreach ($returnTypes as $returnType) {
            if (is_array($returnType)) {
                $returnTypesArray[] = $this->transformReturnTypes($returnType);

                continue;
            }

            $returnTypesArray[] = [
                'title' => $returnType->getTitle(),
                'type' => $returnType->getType(),
                'config' => $returnType->getConfig(),
            ];
        }

        return $returnTypesArray;
    }
}
