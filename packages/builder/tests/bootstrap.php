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

require_once __DIR__.'/TestCase.php';
