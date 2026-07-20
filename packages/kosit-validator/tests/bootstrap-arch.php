<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

if (class_exists(ClassLoader::class, false)) {
    return;
}

$autoload = dirname(__DIR__, 3).'/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException(
        'Run package tests from the monorepo root vendor (../../vendor). '
        .'Example: cd packages/kosit-validator && composer test:arch'
    );
}

require_once $autoload;
