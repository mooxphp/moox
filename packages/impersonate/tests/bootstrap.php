<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

require dirname(__DIR__, 3).'/vendor/autoload.php';

foreach (spl_autoload_functions() ?: [] as $autoloader) {
    if (! is_array($autoloader)) {
        continue;
    }

    $loader = $autoloader[0];

    if ($loader instanceof ClassLoader) {
        $loader->addPsr4('Moox\\Impersonate\\Tests\\', dirname(__DIR__).'/tests');
        $loader->addPsr4('Moox\\Impersonate\\', dirname(__DIR__).'/src');

        break;
    }
}

require dirname(__DIR__).'/tests/Pest.php';
