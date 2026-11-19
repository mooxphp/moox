<?php

declare(strict_types=1);
use Composer\Autoload\ClassLoader;

$autoload = dirname(__DIR__, 3).'/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException(
        'Run package tests from the monorepo root vendor (../../vendor). '
        .'Example: cd packages/builder && composer test'
    );
}

if (! class_exists(ClassLoader::class, false)) {
    require_once $autoload;
}

$loader = require $autoload;
if (method_exists($loader, 'addPsr4')) {
    $loader->addPsr4('Moox\\Builder\\', dirname(__DIR__).'/src/');
    $loader->addPsr4('Moox\\Builder\\Tests\\', __DIR__.'/');
    $loader->addPsr4('Moox\\Builder\\Tests\\Support\\', __DIR__.'/Support/');
}

require_once __DIR__.'/TestCase.php';
