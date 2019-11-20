<?php
declare(strict_types=1);

use GibsonOS\Core\Factory\AbstractSingletonFactory;
use GibsonOS\Core\Utility\Event\CodeGeneratorService;

class CodeGeneratorFactory extends AbstractSingletonFactory
{
    protected static function createInstance(): CodeGeneratorService
    {
        return new CodeGeneratorService();
    }

    public static function create(): CodeGeneratorService
    {
        /** @var CodeGeneratorService $service */
        $service = parent::create();

        return $service;
    }
}
