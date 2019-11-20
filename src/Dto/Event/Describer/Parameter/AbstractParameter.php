<?php
declare(strict_types=1);

namespace GibsonOS\Module\Hc\Dto\Event\Describer\Parameter;

abstract class AbstractParameter
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $listeners = [];

    abstract protected function getTypeConfig(): array;

    /**
     * AbstractParameter constructor.
     *
     * @param string $title
     * @param string $type
     */
    public function __construct(string $title, string $type)
    {
        $this->title = $title;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return array_merge([
            'listeners' => $this->listeners,
        ], $this->getTypeConfig());
    }

    /**
     * @param string $field
     * @param array  $options
     */
    public function setListener(string $field, array $options): void
    {
        $this->listeners[$field] = $options;
    }
}
